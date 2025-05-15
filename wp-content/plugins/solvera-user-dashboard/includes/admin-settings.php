<?php
/*
Author: Alieinyk Liudmyla
Email: alieinyk.liudmyla@gmail.com
License: Proprietary (closed)
*/
// Add menu item in admin panel
add_action('admin_menu', function() {
    add_menu_page(
        'Solvera Интеграции',
        'Solvera Интеграции', 
        'manage_options',
        'solvera-integrations',
        'solvera_admin_integrations_page',
        'dashicons-admin-generic'
    );
});

function solvera_admin_integrations_page() {
    // Save settings
    if (isset($_POST['solvera_save_integrations'])) {
        check_admin_referer('solvera_integrations_save');
        update_option('solvera_stripe_secret_key', sanitize_text_field($_POST['solvera_stripe_secret_key']));
        update_option('solvera_stripe_publishable_key', sanitize_text_field($_POST['solvera_stripe_publishable_key']));
        update_option('solvera_openai_api_key', sanitize_text_field($_POST['solvera_openai_api_key']));
        update_option('solvera_gemini_api_key', sanitize_text_field($_POST['solvera_gemini_api_key']));
        echo '<div class="updated"><p>Настройки сохранены.</p></div>';
    }

    $stripe_secret = get_option('solvera_stripe_secret_key', '');
    $stripe_publishable = get_option('solvera_stripe_publishable_key', '');
    $openai_key = get_option('solvera_openai_api_key', ''); 
    $gemini_key = get_option('solvera_gemini_api_key', '');

    ?>
    <div class="wrap">
        <h1>Настройки интеграций Solvera</h1>
        <form method="post">
            <?php wp_nonce_field('solvera_integrations_save'); ?>
            <h2>Stripe</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="solvera_stripe_secret_key">Secret Key</label></th>
                    <td><input type="text" name="solvera_stripe_secret_key" id="solvera_stripe_secret_key" value="<?php echo esc_attr($stripe_secret); ?>" size="50"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="solvera_stripe_publishable_key">Publishable Key</label></th>
                    <td><input type="text" name="solvera_stripe_publishable_key" id="solvera_stripe_publishable_key" value="<?php echo esc_attr($stripe_publishable); ?>" size="50"></td>
                </tr>
                <tr>
                    <th>OpenAI API Key</th>
                    <td><input type="text" name="solvera_openai_api_key" value="<?php echo esc_attr($openai_key); ?>" style="width:400px;"></td>
                </tr>
                <tr>
                    <th>Gemini API Key</th>
                    <td><input type="text" name="solvera_gemini_api_key" value="<?php echo esc_attr($gemini_key); ?>" style="width:400px;"></td>
                </tr>
            </table>
            <p>
                <input type="submit" name="solvera_save_integrations" class="button-primary" value="Сохранить настройки">
            </p>
        </form>
    </div>
    <?php
}

add_action('admin_menu', function() {
    add_menu_page(
        'Google Ads (агентский доступ)',
        'Google Ads (агентский доступ)',
        'manage_options', 
        'solvera-google-ads-integration',
        'solvera_google_ads_integration_page',
        'dashicons-megaphone'
    );
    
    add_menu_page(
        'Facebook Ads (агентский доступ)',
        'Facebook Ads (агентский доступ)',
        'manage_options',
        'solvera-facebook-ads-integration',
        'solvera_facebook_ads_integration_page',
        'dashicons-facebook'
    );
});

function solvera_google_ads_integration_page() {
    // Save Google Ads settings
    if (isset($_POST['solvera_save_google_ads'])) {
        check_admin_referer('solvera_google_ads_save');
        update_option('solvera_google_ads_mcc_id', sanitize_text_field($_POST['solvera_google_ads_mcc_id']));
        update_option('solvera_google_ads_dev_token', sanitize_text_field($_POST['solvera_google_ads_dev_token']));
        update_option('solvera_google_ads_client_id', sanitize_text_field($_POST['solvera_google_ads_client_id']));
        update_option('solvera_google_ads_client_secret', sanitize_text_field($_POST['solvera_google_ads_client_secret']));
        update_option('solvera_google_ads_refresh_token', sanitize_text_field($_POST['solvera_google_ads_refresh_token']));
        echo '<div class="updated"><p>Настройки сохранены.</p></div>';
    }

    $mcc_id = get_option('solvera_google_ads_mcc_id', '');
    $dev_token = get_option('solvera_google_ads_dev_token', '');
    $client_id = get_option('solvera_google_ads_client_id', '');
    $client_secret = get_option('solvera_google_ads_client_secret', '');
    $refresh_token = get_option('solvera_google_ads_refresh_token', '');

    ?>
    <div class="wrap">
        <h1>Интеграция с Google Ads (агентский аккаунт)</h1>
        <form method="post">
            <?php wp_nonce_field('solvera_google_ads_save'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="solvera_google_ads_mcc_id">Google Ads MCC ID</label></th>
                    <td><input type="text" name="solvera_google_ads_mcc_id" id="solvera_google_ads_mcc_id" value="<?php echo esc_attr($mcc_id); ?>" style="width:350px;"></td>
                </tr>
                <tr>
                    <th><label for="solvera_google_ads_dev_token">Developer Token</label></th>
                    <td><input type="text" name="solvera_google_ads_dev_token" id="solvera_google_ads_dev_token" value="<?php echo esc_attr($dev_token); ?>" style="width:350px;"></td>
                </tr>
                <tr>
                    <th><label for="solvera_google_ads_client_id">OAuth2 Client ID</label></th>
                    <td><input type="text" name="solvera_google_ads_client_id" id="solvera_google_ads_client_id" value="<?php echo esc_attr($client_id); ?>" style="width:350px;"></td>
                </tr>
                <tr>
                    <th><label for="solvera_google_ads_client_secret">OAuth2 Client Secret</label></th>
                    <td><input type="text" name="solvera_google_ads_client_secret" id="solvera_google_ads_client_secret" value="<?php echo esc_attr($client_secret); ?>" style="width:350px;"></td>
                </tr>
                <tr>
                    <th><label for="solvera_google_ads_refresh_token">Refresh Token</label></th>
                    <td>
                        <input type="text" name="solvera_google_ads_refresh_token" id="solvera_google_ads_refresh_token" value="<?php echo esc_attr($refresh_token); ?>" style="width:350px;">
                        <p class="description">Получите через OAuth2 авторизацию (см. инструкцию ниже).</p>
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" name="solvera_save_google_ads" class="button button-primary" value="Сохранить настройки">
            </p>
        </form>
        <h2>Как получить Refresh Token?</h2>
        <ol>
            <li>Создайте OAuth2 Client ID и Client Secret в Google Cloud Console.</li>
            <li>Укажите их выше и сохраните.</li>
            <li>Перейдите по <a href="https://developers.google.com/google-ads/api/docs/oauth/cloud-project" target="_blank">инструкции Google</a> для получения refresh token.</li>
            <li>Вставьте полученный refresh token в поле выше и сохраните.</li>
        </ol>
    </div>
    <?php
}

function solvera_facebook_ads_integration_page() {
    // Save Facebook Ads settings
    if (isset($_POST['solvera_save_facebook_ads'])) {
        check_admin_referer('solvera_facebook_ads_save');
        update_option('solvera_facebook_app_id', sanitize_text_field($_POST['solvera_facebook_app_id']));
        update_option('solvera_facebook_app_secret', sanitize_text_field($_POST['solvera_facebook_app_secret']));
        update_option('solvera_facebook_access_token', sanitize_text_field($_POST['solvera_facebook_access_token']));
        update_option('solvera_facebook_agent_ad_account_id', sanitize_text_field($_POST['solvera_facebook_agent_ad_account_id']));
        echo '<div class="updated"><p>Настройки сохранены.</p></div>';
    }

    $app_id = get_option('solvera_facebook_app_id', '');
    $app_secret = get_option('solvera_facebook_app_secret', '');
    $access_token = get_option('solvera_facebook_access_token', '');
    $agent_ad_account_id = get_option('solvera_facebook_agent_ad_account_id', '');

    ?>
    <div class="wrap">
        <h1>Интеграция с Facebook Ads (агентский аккаунт)</h1>
        <form method="post">
            <?php wp_nonce_field('solvera_facebook_ads_save'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="solvera_facebook_app_id">Facebook App ID</label></th>
                    <td><input type="text" name="solvera_facebook_app_id" id="solvera_facebook_app_id" value="<?php echo esc_attr($app_id); ?>" style="width:350px;"></td>
                </tr>
                <tr>
                    <th><label for="solvera_facebook_app_secret">Facebook App Secret</label></th>
                    <td><input type="text" name="solvera_facebook_app_secret" id="solvera_facebook_app_secret" value="<?php echo esc_attr($app_secret); ?>" style="width:350px;"></td>
                </tr>
                <tr>
                    <th><label for="solvera_facebook_agent_ad_account_id">Agent Ad Account ID</label></th>
                    <td><input type="text" name="solvera_facebook_agent_ad_account_id" id="solvera_facebook_agent_ad_account_id" value="<?php echo esc_attr($agent_ad_account_id); ?>" style="width:350px;">
                        <p class="description">ID агентского рекламного аккаунта Facebook (например, 123456789012345). Все кампании будут создаваться через этот аккаунт.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="solvera_facebook_access_token">Access Token</label></th>
                    <td>
                        <input type="text" name="solvera_facebook_access_token" id="solvera_facebook_access_token" value="<?php echo esc_attr($access_token); ?>" style="width:350px;">
                        <p class="description">Получите через Facebook Login (см. инструкцию ниже).</p>
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" name="solvera_save_facebook_ads" class="button button-primary" value="Сохранить настройки">
            </p>
        </form>
        <h2>Как получить Access Token?</h2>
        <ol>
            <li>Создайте приложение в Facebook Developers Console.</li>
            <li>Укажите App ID и App Secret выше и сохраните.</li>
            <li>Перейдите по <a href="https://developers.facebook.com/docs/facebook-login/access-tokens/" target="_blank">инструкции Facebook</a> для получения access token.</li>
            <li>Вставьте полученный access token в поле выше и сохраните.</li>
        </ol>
    </div>
    <?php
}

add_action('admin_menu', function() {
    add_menu_page(
        'Solvera User Meta',
        'Solvera User Meta',
        'manage_options',
        'solvera-user-meta',
        'solvera_user_meta_page', 
        'dashicons-admin-users',
        99
    );
});

function solvera_user_meta_page() {
    global $wpdb;
    $users = get_users();
    echo '<div class="wrap"><h1>Solvera User Meta</h1>';
    echo '<table class="widefat fixed" style="font-size:13px;"><thead><tr>';
    echo '<th>Логин</th><th>Email</th><th>Роль</th><th>Дата регистрации</th><th>Тариф</th><th>Сайтов</th><th>Кампаний</th>';
    echo '<th>Google refresh_token</th><th>Google customer_id</th><th>Facebook access_token</th><th>Facebook ad_account</th>';
    echo '</tr></thead><tbody>';
    foreach ($users as $user) {
        $plan = get_user_meta($user->ID, 'solvera_plan', true);
        $reg = $user->user_registered;
        $refresh_token = get_user_meta($user->ID, 'solvera_google_ads_refresh_token', true);
        $customer_id = get_user_meta($user->ID, 'solvera_google_ads_customer_id', true);
        $fb_token = get_user_meta($user->ID, 'solvera_facebook_access_token', true);
        $fb_ad = get_user_meta($user->ID, 'solvera_facebook_ad_account', true);
        $sites = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}solvera_websites WHERE user_id = %d", $user->ID));
        $campaigns = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}solvera_campaigns WHERE user_id = %d", $user->ID));
        echo '<tr>';
        echo '<td>' . esc_html($user->user_login) . '</td>';
        echo '<td>' . esc_html($user->user_email) . '</td>';
        echo '<td>' . esc_html(implode(', ', $user->roles)) . '</td>'; 
        echo '<td>' . esc_html($reg) . '</td>';
        echo '<td>' . esc_html($plan) . '</td>';
        echo '<td>' . intval($sites) . '</td>';
        echo '<td>' . intval($campaigns) . '</td>';
        echo '<td style="max-width:180px;word-break:break-all;">' . esc_html($refresh_token) . '</td>';
        echo '<td>' . esc_html($customer_id) . '</td>';
        echo '<td style="max-width:180px;word-break:break-all;">' . esc_html($fb_token) . '</td>';
        echo '<td>' . esc_html($fb_ad) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}