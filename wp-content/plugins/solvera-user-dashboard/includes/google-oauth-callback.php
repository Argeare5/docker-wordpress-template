<?php
/*
Author: Alieinyk Liudmyla
Email: alieinyk.liudmyla@gmail.com
License: Proprietary (closed)
*/
require_once __DIR__ . '/../vendor/autoload.php';
// OAuth callback handler for Google Ads
add_action('init', function() {
    if (isset($_GET['code']) && strpos($_SERVER['REQUEST_URI'], '/google-oauth-callback') !== false) {
        $code = $_GET['code'];
        $client_id = get_option('solvera_google_ads_client_id', '');
        $client_secret = get_option('solvera_google_ads_client_secret', '');
        $redirect_uri = home_url('/google-oauth-callback');

        // Get access_token and refresh_token
        $token_url = 'https://oauth2.googleapis.com/token';
        $response = wp_remote_post($token_url, [
            'body' => [
                'code' => $code,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code'
            ]
        ]);
        if (is_wp_error($response)) {
            wp_die('Token retrieval error: ' . $response->get_error_message());
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['refresh_token'])) {
            wp_die('Error: refresh_token not received');
        }
        $refresh_token = $body['refresh_token'];
        $access_token = $body['access_token'] ?? '';

        $user_id = get_current_user_id();
        update_user_meta($user_id, 'solvera_google_ads_refresh_token', $refresh_token);
        update_user_meta($user_id, 'solvera_google_ads_access_token', $access_token);

        // Get customer_id via Google Ads API PHP Client Library
        try {
            $developerToken = get_option('solvera_google_ads_dev_token', '');
            $oAuth2Credential = (new Google\Auth\OAuth2([ 
                'clientId' => $client_id,
                'clientSecret' => $client_secret,
                'refreshToken' => $refresh_token,
            ]))->fetchAuthToken();

            $googleAdsClient = (new Google\Ads\GoogleAds\Lib\V14\GoogleAdsClientBuilder())
                ->withDeveloperToken($developerToken)
                ->withOAuth2Credential(new Google\Auth\FetchAuthTokenCache([
                    'clientId' => $client_id,
                    'clientSecret' => $client_secret,
                    'refreshToken' => $refresh_token,
                ]))
                ->build();

            $customerService = $googleAdsClient->getCustomerServiceClient();
            $accessibleCustomers = $customerService->listAccessibleCustomers();
            $resourceNames = $accessibleCustomers->getResourceNames();
            if (count($resourceNames) > 0) {
                if (preg_match('!/customers/(\d+)!', $resourceNames[0], $m)) {
                    $customer_id = $m[1];
                    update_user_meta($user_id, 'solvera_google_ads_customer_id', $customer_id);
                }
            }
        } catch (Exception $e) {
            error_log('Google Ads SDK error: ' . $e->getMessage());
        }

        // Redirect back to integrations
        wp_redirect('/integrations');
        exit;
    }
}); 