<?php
/*
Plugin Name: Solvera User Dashboard
Description: Personal user cabinet.
Version: 1.0
Author: Solvera
Author Email: alieinyk.liudmyla@gmail.com
License: Proprietary (closed)
*/

// Include all required files
$includes = [
    'plans', 'admin-settings', 'stripe-handler', 'dashboard-menu',
    'integrations', 'ai-assistant', 'campaigns', 'dashboard',
    'referrals', 'current-plan', 'analytics', 'user-dashboard-router',
    'tracker-endpoint', 'google-ads-create', 'support',
    'facebook-oauth-callback', 'google-oauth-callback'
];

foreach ($includes as $file) {
    require_once __DIR__ . '/includes/' . $file . '.php';
}

// Handle referral tracking
add_action('init', function() {
    if (isset($_GET['ref'])) {
        setcookie('solvera_ref', intval($_GET['ref']), time() + 30*24*60*60, '/');
    }
});

function solvera_get_plans() {
    return [
        'free_trial' => [
            'name' => 'Пробный период (30 дней)',
            'price' => 0,
            'duration' => 30, // days
            'features' => ['Facebook и Google Ads', '5 кампаний', 'AI-ассистент', 'Кросс-платформенная оптимизация'],
        ],
        'basic' => [
            'name' => 'Базовый тариф',
            'price' => 29.99,
            'duration' => 30,
            'features' => ['Facebook Ads', '3 кампании'],
        ],
        'pro' => [
            'name' => 'Продвинутый тариф',
            'price' => 49.99,
            'duration' => 30,
            'features' => ['Facebook и Google Ads', '7 кампаний', 'Кросс-платформенная оптимизация'],
        ],
        'business' => [
            'name' => 'Бизнес тариф',
            'price' => 99.99,
            'duration' => 30,
            'features' => ['Facebook и Google Ads', '20 кампаний', 'AI-ассистент', 'Кросс-платформенная оптимизация'],
        ],
    ];
}

// Handle new user registration
add_action('user_register', function($user_id) {
    $plans = solvera_get_plans();
    $trial_duration = $plans['free_trial']['duration'];
    
    update_user_meta($user_id, 'solvera_plan', 'free_trial');
    update_user_meta($user_id, 'solvera_plan_expire', date('Y-m-d', strtotime("+{$trial_duration} days")));
    
    if (isset($_COOKIE['solvera_ref'])) {
        $referrer_id = intval($_COOKIE['solvera_ref']);
        if ($referrer_id && $referrer_id !== $user_id) {
            update_user_meta($user_id, 'solvera_referrer', $referrer_id);
        }
    }
});

function solvera_user_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Пожалуйста, <a href="/login">войдите</a> в свой аккаунт.</p>';
    }

    $current_user = wp_get_current_user();
    ob_start();
    ?>
    <h3>Профиль</h3>
    <p>Имя пользователя: <strong><?php echo esc_html($current_user->display_name); ?></strong></p>
    <p>Email: <strong><?php echo esc_html($current_user->user_email); ?></strong></p>
    <?php
    return ob_get_clean();
}
add_shortcode('solvera_user_dashboard', 'solvera_user_dashboard_shortcode');

// Enqueue dashboard styles for logged-in users only
add_action('wp_enqueue_scripts', function() {
    if (is_user_logged_in()) {
        wp_enqueue_style(
            'solvera-dashboard-style',
            plugins_url('assets/dashboard.css', __FILE__),
            [],
            '1.0'
        );
    }
});