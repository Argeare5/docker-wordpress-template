<?php
// Facebook OAuth callback handler
add_action('init', function() {
    if (isset($_GET['code']) && strpos($_SERVER['REQUEST_URI'], '/facebook-oauth-callback') !== false) {
        $code = $_GET['code'];
        $app_id = get_option('solvera_facebook_app_id', '');
        $app_secret = get_option('solvera_facebook_app_secret', '');
        $redirect_uri = home_url('/facebook-oauth-callback');

        // Get access token
        $token_url = 'https://graph.facebook.com/v18.0/oauth/access_token?'
            . 'client_id=' . urlencode($app_id)
            . '&redirect_uri=' . urlencode($redirect_uri)
            . '&client_secret=' . urlencode($app_secret)
            . '&code=' . urlencode($code);

        $response = wp_remote_get($token_url);
        if (is_wp_error($response)) {
            wp_die('Token retrieval error: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['access_token'])) {
            wp_die('Error: access token not received');
        }

        $access_token = $body['access_token'];

        // Get user's business pages
        $pages_url = 'https://graph.facebook.com/v18.0/me/accounts?access_token=' . urlencode($access_token);
        $pages_response = wp_remote_get($pages_url);
        if (is_wp_error($pages_response)) {
            wp_die('Error getting business pages: ' . $pages_response->get_error_message());
        }
        $pages_data = json_decode(wp_remote_retrieve_body($pages_response), true);
        if (!isset($pages_data['data']) || empty($pages_data['data'])) {
            wp_die('Error: no business pages found. Make sure you have access to a Facebook business page.');
        }

        $user_id = get_current_user_id();
        update_user_meta($user_id, 'solvera_facebook_access_token', $access_token);

        // If single page - save immediately, if multiple - save array for selection
        if (count($pages_data['data']) === 1) {
            update_user_meta($user_id, 'solvera_facebook_page_id', $pages_data['data'][0]['id']);
            update_user_meta($user_id, 'solvera_facebook_page_name', $pages_data['data'][0]['name']);
        } else {
            update_user_meta($user_id, 'solvera_facebook_pages', $pages_data['data']);
            // Redirect to integrations with page selection parameter
            wp_redirect('/integrations?fb_choose_page=1');
            exit;
        }

        // Get ad accounts information
        $accounts_url = 'https://graph.facebook.com/v18.0/me/adaccounts?'
            . 'access_token=' . urlencode($access_token)
            . '&fields=id,name,account_id';

        $accounts_response = wp_remote_get($accounts_url);
        if (is_wp_error($accounts_response)) {
            wp_die('Error getting ad accounts: ' . $accounts_response->get_error_message());
        }

        $accounts_data = json_decode(wp_remote_retrieve_body($accounts_response), true);
        if (!isset($accounts_data['data']) || empty($accounts_data['data'])) {
            wp_die('Error: no ad accounts found');
        }

        update_user_meta($user_id, 'solvera_facebook_ad_account', $accounts_data['data'][0]['id']);

        // Redirect back to dashboard
        wp_redirect('/integrations');
        exit;
    }
});