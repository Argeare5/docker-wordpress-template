<?php
function solvera_current_plan_shortcode() {
    if (!is_user_logged_in()) return '';
    $current_user = wp_get_current_user();
    $current_plan = get_user_meta($current_user->ID, 'solvera_plan', true);
    $plan_expire = get_user_meta($current_user->ID, 'solvera_plan_expire', true);
    $plans = function_exists('solvera_get_plans') ? solvera_get_plans() : [];

    ob_start();
    ?>
    <div class="solvera-dashboard-content">
        <div class="solvera-card">
            <?php if ($current_plan && isset($plans[$current_plan])): ?>
                <div class="solvera-current-plan">
                    <div class="solvera-plan-header">
                        <div class="solvera-plan-icon">✨</div>
                        <div class="solvera-plan-info">
                            <h2 class="solvera-plan-title"><?php echo esc_html($plans[$current_plan]['name']); ?></h2>
                            <div class="solvera-plan-expiry">
                                Действует до: <strong><?php echo esc_html($plan_expire); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="solvera-plan-progress">
                        <?php
                        // Calculate plan progress
                        $duration = isset($plans[$current_plan]['duration']) ? (int)$plans[$current_plan]['duration'] : 30; 
                        $end_date = strtotime($plan_expire);
                        $start_date = $end_date - $duration * DAY_IN_SECONDS;
                        $current_date = time();
                        $total_days = $duration;
                        $days_passed = floor(($current_date - $start_date) / DAY_IN_SECONDS);
                        $days_left = ceil(($end_date - $current_date) / DAY_IN_SECONDS);
                        $days_left = max(0, $days_left);
                        $days_passed = max(0, min($days_passed, $total_days));
                        $progress = ($days_passed / $total_days) * 100;
                        ?>
                        <div class="solvera-progress-bar">
                            <div class="solvera-progress-fill" style="width: <?php echo esc_attr($progress); ?>%"></div>
                        </div>
                        <div class="solvera-progress-text">
                            Осталось: <?php echo esc_html($days_left); ?> дней
                        </div>
                    </div>

                    <div class="solvera-plan-features">
                        <h3 class="solvera-features-title">Возможности тарифа</h3>
                        <ul class="solvera-features-list">
                            <?php foreach ($plans[$current_plan]['features'] as $feature): ?>
                                <li class="solvera-feature-item">
                                    <span class="solvera-feature-icon">✓</span>
                                    <?php echo esc_html($feature); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="solvera-plan-actions">
                        <a href="/change-plan" class="solvera-btn solvera-btn-primary">Сменить тариф</a>
                    </div>
                </div>

                <div class="solvera-payment-history">
                    <h3 class="solvera-history-title">История оплат</h3>
                    <?php 
                    // Get user transactions
                    $transactions = function_exists('solvera_get_user_transactions') ? solvera_get_user_transactions($current_user->ID) : [];
                    if ($transactions): ?>
                        <div class="solvera-transactions-list">
                            <?php foreach ($transactions as $tr): ?>
                                <div class="solvera-transaction-item">
                                    <div class="solvera-transaction-date">
                                        <?php echo esc_html(date('d.m.Y H:i', strtotime($tr->paid_at))); ?>
                                    </div>
                                    <div class="solvera-transaction-plan">
                                        <?php echo esc_html($plans[$tr->plan_id]['name'] ?? $tr->plan_id); ?>
                                    </div>
                                    <div class="solvera-transaction-amount">
                                        <?php echo esc_html($tr->amount); ?> €
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="solvera-empty-state">
                            <div class="solvera-empty-icon">📝</div>
                            <div class="solvera-empty-text">Оплат пока не было</div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="solvera-no-plan">
                    <div class="solvera-empty-icon">🎯</div>
                    <h2 class="solvera-empty-title">У вас нет активного тарифа</h2>
                    <p class="solvera-empty-text">Выберите подходящий тариф, чтобы начать работу с сервисом</p>
                    <a href="/choose-plan" class="solvera-btn solvera-btn-primary">Выбрать тариф</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('solvera_current_plan', 'solvera_current_plan_shortcode');