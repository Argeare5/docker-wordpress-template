<?php
// Shortcode for plan selection page
add_shortcode('solvera_choose_plan', 'solvera_choose_plan_shortcode');

function solvera_choose_plan_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Пожалуйста, <a href="/login">войдите</a> в свой аккаунт.</p>';
    }

    $current_user = wp_get_current_user();
    $current_plan = get_user_meta($current_user->ID, 'solvera_plan', true); 
    $plans = function_exists('solvera_get_plans') ? solvera_get_plans() : [];

    ob_start();
    ?>
    <div class="solvera-dashboard-content">
        <div class="solvera-title">Выбор тарифа</div>
        <div class="solvera-plans-list">
            <?php foreach ($plans as $plan_id => $plan):
                $is_current = ($current_plan === $plan_id);
                $icon = '✨';
                if ($plan_id === 'basic') $icon = '💡';
                if ($plan_id === 'pro') $icon = '🚀';
                if ($plan_id === 'business') $icon = '🏆';
            ?>
                <div class="solvera-plan-card<?php if ($is_current) echo ' solvera-plan-card-current'; ?>">
                    <div class="solvera-plan-card-header">
                        <div class="solvera-plan-card-icon"><?php echo $icon; ?></div>
                        <div class="solvera-plan-card-title"><?php echo esc_html($plan['name']); ?></div>
                    </div>
                    <div class="solvera-plan-card-price">
                        <?php if ($plan['price'] > 0): ?>
                            <span><?php echo esc_html($plan['price']); ?> <span class="solvera-plan-card-currency">€</span> <span class="solvera-plan-card-period">/ мес</span></span>
                        <?php else: ?>
                            <span class="solvera-plan-card-free">Бесплатно</span>
                        <?php endif; ?>
                    </div>
                    <ul class="solvera-plan-card-features">
                        <?php foreach ($plan['features'] as $feature): ?>
                            <li><span class="solvera-plan-card-feature-icon">✓</span> <?php echo esc_html($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="solvera-plan-card-actions">
                        <?php if ($is_current): ?>
                            <span class="solvera-plan-card-current-label">Ваш тариф</span>
                        <?php elseif ($plan['price'] == 0): ?>
                            <form method="post">
                                <input type="hidden" name="solvera_select_plan" value="<?php echo esc_attr($plan_id); ?>">
                                <button type="submit" class="solvera-btn solvera-btn-primary">Выбрать</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?php echo esc_url( add_query_arg('solvera_stripe_checkout', '1', home_url('/')) ); ?>">
                                <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan_id); ?>">
                                <button type="submit" class="solvera-btn solvera-btn-primary">Оплатить через Stripe</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    // Process free plan selection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solvera_select_plan'])) {
        $plan_id = sanitize_text_field($_POST['solvera_select_plan']);
        if (isset($plans[$plan_id]) && $plans[$plan_id]['price'] == 0) {
            update_user_meta($current_user->ID, 'solvera_plan', $plan_id);
            update_user_meta($current_user->ID, 'solvera_plan_expire', date('Y-m-d', strtotime('+' . $plans[$plan_id]['duration'] . ' days')));
            echo '<div style="color:green;">Тариф успешно выбран!</div>';
            echo '<script>setTimeout(function(){location.reload();}, 1000);</script>';
        }
    }

    return ob_get_clean();
}

// Create transactions table on plugin activation
register_activation_hook(__FILE__, function() {
    global $wpdb;
    $table = $wpdb->prefix . 'solvera_transactions';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        plan_id VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        paid_at DATETIME NOT NULL,
        stripe_session_id VARCHAR(255) DEFAULT NULL
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

// Handle successful Stripe payment
add_action('init', function() {
    if (is_user_logged_in() && isset($_GET['success'], $_GET['plan']) && $_GET['success'] == '1') {
        $plan_id = sanitize_text_field($_GET['plan']);
        $plans = function_exists('solvera_get_plans') ? solvera_get_plans() : [];
        if (isset($plans[$plan_id]) && $plans[$plan_id]['price'] > 0) {
            $user_id = get_current_user_id();
            $expire = date('Y-m-d', strtotime('+' . $plans[$plan_id]['duration'] . ' days'));
            update_user_meta($user_id, 'solvera_plan', $plan_id);
            update_user_meta($user_id, 'solvera_plan_expire', $expire);
            // Record transaction
            global $wpdb;
            $table = $wpdb->prefix . 'solvera_transactions';
            $wpdb->insert($table, [
                'user_id' => $user_id,
                'plan_id' => $plan_id,
                'amount' => $plans[$plan_id]['price'],
                'paid_at' => current_time('mysql'),
                'stripe_session_id' => null // Can be updated to store session_id
            ]);
            // Success message
            add_action('wp_footer', function() {
                echo '<div style="position:fixed;top:20px;right:20px;background:#dff0d8;color:#3c763d;padding:15px;z-index:9999;">Тариф успешно оплачен и активирован!</div>';
                echo '<script>setTimeout(function(){document.querySelectorAll(\'[style*="background:#dff0d8"]\').forEach(e=>e.remove());}, 4000);</script>';
            });
            // Process referral bonus after first payment
            $referrer_id = get_user_meta($user_id, 'solvera_referrer', true);
            $bonus_given = get_user_meta($user_id, 'solvera_referral_bonus_given', true);

            if ($referrer_id && !$bonus_given) {
                $balance = (float) get_user_meta($referrer_id, 'solvera_referral_balance', true);
                $count = (int) get_user_meta($referrer_id, 'solvera_referral_paid_count', true);
                update_user_meta($referrer_id, 'solvera_referral_balance', $balance + 10);
                update_user_meta($referrer_id, 'solvera_referral_paid_count', $count + 1);
                update_user_meta($user_id, 'solvera_referral_bonus_given', 1);
            }
        }
    }
});

// Get user transaction history
function solvera_get_user_transactions($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'solvera_transactions';
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d ORDER BY paid_at DESC", $user_id));
}