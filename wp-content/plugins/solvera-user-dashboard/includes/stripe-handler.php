<?php
add_action('init', function() {
    if (isset($_GET['solvera_stripe_checkout']) && $_GET['solvera_stripe_checkout'] === '1') {
        solvera_handle_stripe_checkout();
        exit;
    }
});

function solvera_handle_stripe_checkout() {
    if (!is_user_logged_in()) {
        wp_die('Authorization required');
    }

    // Initialize and validate plan
    $plan_id = filter_input(INPUT_POST, 'plan_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $plans = function_exists('solvera_get_plans') ? solvera_get_plans() : [];
    if (!$plan_id || !isset($plans[$plan_id]) || $plans[$plan_id]['price'] <= 0) {
        wp_die('Invalid subscription plan');
    }

    // Get Stripe credentials
    $stripe_secret = get_option('solvera_stripe_secret_key', '');
    $stripe_publishable = get_option('solvera_stripe_publishable_key', '');

    if (!$stripe_secret || !$stripe_publishable) {
        wp_die('Stripe configuration missing');
    }

    // Setup payment data
    $amount = intval($plans[$plan_id]['price'] * 100); // Convert to cents
    $user_id = get_current_user_id();
    $success_url = home_url('/change-plan?success=1&plan=' . urlencode($plan_id));
    $cancel_url = home_url('/change-plan?cancel=1');

    // Prepare Stripe checkout data
    $data = [
        'payment_method_types[]' => 'card',
        'line_items[0][price_data][currency]' => 'usd',
        'line_items[0][price_data][product_data][name]' => $plans[$plan_id]['name'],
        'line_items[0][price_data][unit_amount]' => $amount,
        'line_items[0][quantity]' => 1,
        'mode' => 'payment',
        'customer_email' => wp_get_current_user()->user_email,
        'success_url' => $success_url,
        'cancel_url' => $cancel_url,
        'metadata[user_id]' => $user_id,
        'metadata[plan_id]' => $plan_id,
    ];

    // Initialize cURL request
    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $stripe_secret . ':',
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpcode === 200 && !empty($result['url'])) {
        wp_redirect($result['url']);
        exit;
    }
    
    wp_die('Stripe Error: ' . print_r($result, true));
}