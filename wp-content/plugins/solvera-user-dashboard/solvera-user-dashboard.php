<?php
/*
Plugin Name: Solvera User Dashboard
Description: Personal user cabinet.
Version: 1.0
Author: Solvera
Author Email: alieinyk.liudmyla@gmail.com
License: Proprietary (clos ed)
*/

require_once __DIR__ . '/includes/plans.php';
require_once __DIR__ . '/includes/admin-settings.php';
require_once __DIR__ . '/includes/stripe-handler.php';
require_once __DIR__ . '/includes/dashboard-menu.php';
require_once __DIR__ . '/includes/integrations.php';
require_once __DIR__ . '/includes/ai-assistant.php';
require_once __DIR__ . '/includes/campaigns.php';
require_once __DIR__ . '/includes/dashboard.php';
require_once __DIR__ . '/includes/referrals.php';
require_once __DIR__ . '/includes/current-plan.php';
require_once __DIR__ . '/includes/analytics.php';
require_once __DIR__ . '/includes/user-dashboard-router.php';
require_once __DIR__ . '/includes/tracker-endpoint.php';
require_once __DIR__ . '/includes/google-ads-create.php';
require_once __DIR__ . '/includes/support.php';
require_once __DIR__ . '/includes/facebook-oauth-callback.php';
require_once __DIR__ . '/includes/google-oauth-callback.php';

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
            'duration' => 30, // дней
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

add_action('user_register', function($user_id) {
    $plans = solvera_get_plans();
    update_user_meta($user_id, 'solvera_plan', 'free_trial');
    update_user_meta($user_id, 'solvera_plan_expire', date('Y-m-d', strtotime('+' . $plans['free_trial']['duration'] . ' days')));
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
    echo '<h3>Профиль</h3>';
    echo '<p>Имя пользователя: <strong>' . esc_html($current_user->display_name) . '</strong></p>';
    echo '<p>Email: <strong>' . esc_html($current_user->user_email) . '</strong></p>';
    return ob_get_clean();
}

add_shortcode('solvera_user_dashboard', 'solvera_user_dashboard_shortcode');

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