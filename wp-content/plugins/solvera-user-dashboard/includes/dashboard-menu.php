<?php
// Shortcode for user dashboard menu
add_shortcode('solvera_dashboard_menu', 'solvera_dashboard_menu_shortcode');

function solvera_dashboard_menu_shortcode() {
    if (!is_user_logged_in()) {
        return '';
    }

    $current_user = wp_get_current_user();
    $username = $current_user->user_login;

    // Tabs array: 'URL' => ['Label', 'emoji-icon'] 
    $tabs = [
        "/dashboard/$username"      => ['Дашборд', '🏠'],
        "/current-plan"             => ['Мой тариф', '💳'],
        "/integrations"             => ['Интеграции', '🔗'],
        "/ai-assistant"             => ['AI-ассистент', '🤖'],
        "/campaigns"                => ['Кампании', '📢'],
        "/analytics"                => ['Аналитика', '📊'],
        "/referrals"                => ['Рефералы', '🎁'],
        "/support"                  => ['Поддержка', '🛟'],
        "/profile"                  => ['Профиль', '👤'],
    ];

    // Get current URL without parameters
    $current_url = strtok($_SERVER['REQUEST_URI'], '?');

    ob_start();
    ?>
    <nav class="solvera-dashboard-menu solvera-dashboard-menu-vertical">
        <div style="text-align:center;margin-bottom:28px;">
            <a href="/dashboard/<?php echo esc_attr($username); ?>" style="text-decoration:none;display:flex;align-items:center;justify-content:center;gap:10px;">
                <span style="font-size:2.1rem;color:#7c3aed;font-weight:900;">🟣</span>
                <span style="font-size:1.35rem;font-weight:800;color:#5B21B6;letter-spacing:-1px;">Solvera</span>
            </a>
        </div>
        <ul class="solvera-dashboard-tab-list">
            <?php foreach ($tabs as $url => [$label, $icon]): 
                $active = ($current_url === $url) ? 'active' : '';
            ?>
                <li><a href="<?php echo esc_attr($url); ?>" class="<?php echo $active; ?>">
                    <span style="font-size:1.18rem;margin-right:10px;vertical-align:middle;"><?php echo $icon; ?></span>
                    <span><?php echo esc_html($label); ?></span>
                </a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php
    return ob_get_clean();
}