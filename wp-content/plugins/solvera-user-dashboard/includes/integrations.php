<?php
/*
Author: Alieinyk Liudmyla
Email: alieinyk.liudmyla@gmail.com
License: Proprietary (closed)
*/
add_shortcode('solvera_integrations', function() {
    if (!is_user_logged_in()) return '';

    global $wpdb;
    $user_id = get_current_user_id();
    $table = $wpdb->prefix . 'solvera_websites';

    // --- TEMPORARY! Create table on each page load ---
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        site_name VARCHAR(255) NOT NULL,
        site_url VARCHAR(255) NOT NULL,
        tracking_code VARCHAR(64) NOT NULL, 
        status VARCHAR(32) DEFAULT 'pending',
        google_ads_status TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    // --- end temporary block ---

    // Handle site addition
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solvera_add_website'])) {
        $site_name = sanitize_text_field($_POST['site_name']);
        $site_url = esc_url_raw($_POST['site_url']); 
        $tracking_code = 'solvera-' . $user_id . '-' . wp_generate_password(8, false, false);

        $wpdb->insert($table, [
            'user_id' => $user_id,
            'site_name' => $site_name,
            'site_url' => $site_url,
            'tracking_code' => $tracking_code,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ]);
        echo '<div style="color:green;">Site added!</div>';
    }

    // Handle site deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solvera_delete_website'], $_POST['delete_site_id'])) {
        $delete_site_id = intval($_POST['delete_site_id']);
        $wpdb->delete($table, ['id' => $delete_site_id, 'user_id' => $user_id]);
        echo '<div style="color:red;">Site deleted!</div>';
    }

    // Get user sites
    $sites = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));

    // --- Google Ads OAuth ---
    $google_client_id = get_option('solvera_google_ads_client_id', '');
    $google_client_secret = get_option('solvera_google_ads_client_secret', '');
    $google_redirect_uri = get_option('solvera_google_ads_redirect_uri', home_url('/google-oauth-callback'));
    $google_scope = 'https://www.googleapis.com/auth/adwords';
    $user_refresh_token = get_user_meta($user_id, 'solvera_google_ads_refresh_token', true);
    $user_customer_id = get_user_meta($user_id, 'solvera_google_ads_customer_id', true);
    $google_connected = $user_refresh_token && $user_customer_id;
    $oauth_url = 'https://accounts.google.com/o/oauth2/v2/auth?response_type=code'
        . '&client_id=' . urlencode($google_client_id)
        . '&redirect_uri=' . urlencode($google_redirect_uri)
        . '&scope=' . urlencode($google_scope)
        . '&access_type=offline&prompt=consent';

    // --- Facebook Ads OAuth ---
    $facebook_app_id = get_option('solvera_facebook_app_id', '');
    $facebook_app_secret = get_option('solvera_facebook_app_secret', '');
    $facebook_redirect_uri = home_url('/facebook-oauth-callback');
    $facebook_scope = 'ads_management,business_management';
    $user_facebook_token = get_user_meta($user_id, 'solvera_facebook_access_token', true);
    $user_facebook_ad_account = get_user_meta($user_id, 'solvera_facebook_ad_account', true);
    $facebook_connected = $user_facebook_token && $user_facebook_ad_account;
    $facebook_oauth_url = 'https://www.facebook.com/v18.0/dialog/oauth?'
        . 'client_id=' . urlencode($facebook_app_id)
        . '&redirect_uri=' . urlencode($facebook_redirect_uri)
        . '&scope=' . urlencode($facebook_scope)
        . '&response_type=code';

    // --- Facebook Business Pages: choose page if multiple ---
    if (isset($_GET['fb_choose_page']) && get_user_meta($user_id, 'solvera_facebook_pages', true)) {
        $pages = get_user_meta($user_id, 'solvera_facebook_pages', true);
        if (!is_array($pages)) $pages = json_decode($pages, true);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choose_fb_page'])) {
            $page_id = sanitize_text_field($_POST['fb_page_id']);
            $page_name = '';
            foreach ($pages as $p) {
                if ($p['id'] == $page_id) $page_name = $p['name'];
            }
            update_user_meta($user_id, 'solvera_facebook_page_id', $page_id);
            update_user_meta($user_id, 'solvera_facebook_page_name', $page_name);
            delete_user_meta($user_id, 'solvera_facebook_pages');
            echo '<div class="solvera-alert solvera-alert-success">Бизнес-страница Facebook выбрана: <b>' . esc_html($page_name) . '</b></div>';
        } else {
            echo '<div class="solvera-card" style="margin-bottom:32px;">';
            echo '<div class="solvera-title" style="margin-bottom:18px;">Выберите бизнес-страницу Facebook</div>';
            echo '<form method="post" class="solvera-form">';
            echo '<select name="fb_page_id" required style="min-width:260px;">';
            foreach ($pages as $p) {
                echo '<option value="' . esc_attr($p['id']) . '">' . esc_html($p['name']) . '</option>';
            }
            echo '</select>';
            echo '<button type="submit" name="choose_fb_page" class="solvera-btn" style="margin-left:12px;">Выбрать</button>';
            echo '</form>';
            echo '</div>';
        }
    }

    // Handle Facebook business page unlinking
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solvera_facebook_unlink_page'])) {
        delete_user_meta($user_id, 'solvera_facebook_page_id');
        delete_user_meta($user_id, 'solvera_facebook_page_name');
        echo '<div class="solvera-alert">Facebook business page unlinked.</div>';
    }

    ob_start();
    ?>
    <div class="solvera-dashboard-content">
        <div class="solvera-card">
            <div class="solvera-title">Добавить сайт</div>
            <form method="post" class="solvera-form">
                <input type="text" name="site_name" placeholder="Название сайта" required>
                <input type="url" name="site_url" placeholder="https://example.com" required>
                <button type="submit" name="solvera_add_website" class="solvera-btn solvera-btn-primary">Добавить</button>
            </form>
        </div>
        <div class="solvera-card">
            <div class="solvera-title" style="margin-bottom:24px;">Ваши сайты</div>
            <div class="solvera-integrations-list">
                <?php foreach ($sites as $site): ?>
                    <?php
                    $is_connected = ($site->status === 'connected');
                    $is_success = (isset($site->google_ads_status) && preg_match('/^ok\b/i', $site->google_ads_status));
                    $is_error = (isset($site->google_ads_status) && !$is_success && trim($site->google_ads_status) !== '');
                    ?>
                    <div class="solvera-integration-card<?php if ($is_connected) echo ' solvera-integration-card-connected'; ?>">
                        <div class="solvera-integration-card-header">
                            <div class="solvera-integration-card-icon">🌐</div>
                            <div class="solvera-integration-card-info">
                                <div class="solvera-integration-card-title"><?php echo esc_html($site->site_name); ?></div>
                                <div class="solvera-integration-card-url"><a href="<?php echo esc_url($site->site_url); ?>" target="_blank"><?php echo esc_url($site->site_url); ?></a></div>
                            </div>
                        </div>
                        <div class="solvera-integration-card-actions">
                            <div>
                                <button class="solvera-site-btn solvera-site-btn-connect" onclick="openConnectModal(<?php echo $site->id; ?>)"<?php if ($is_connected) echo ' disabled'; ?>>Подключить</button>
                            </div>
                            <div class="solvera-integration-actions-row">
                                <?php if ($is_error): ?>
                                    <span class="solvera-site-error-label" onclick="showErrorComment(<?php echo $site->id; ?>)" title="Посмотреть причину">Ошибка</span>
                                <?php endif; ?>
                                <?php if ($is_success): ?>
                                    <button class="solvera-site-btn solvera-site-btn-check checked" disabled>Проверено</button>
                                <?php else: ?>
                                    <button class="solvera-site-btn solvera-site-btn-check<?php echo $is_error ? ' failed' : ''; ?>" onclick="openCheckModal(<?php echo $site->id; ?>)">Проверить</button>
                                <?php endif; ?>
                            </div>
                            <div>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_site_id" value="<?php echo $site->id; ?>">
                                    <button type="submit" name="solvera_delete_website" class="solvera-site-btn solvera-site-btn-delete" onclick="return confirm('Удалить сайт?');">Удалить</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="solvera-card" style="margin-bottom:32px;">
            <div class="solvera-title" style="margin-bottom:18px;">Интеграция с Google Ads</div>
            <?php if ($google_connected): ?>
                <div style="margin-bottom:12px;color:#43a047;font-weight:600;">Google Ads подключён!</div>
                <div style="margin-bottom:12px;">ID рекламного аккаунта: <b><?php echo esc_html($user_customer_id); ?></b></div>
                <form method="post" style="display:inline;">
                    <button type="submit" name="solvera_google_disconnect" class="solvera-btn solvera-btn-secondary">Отключить</button>
                </form>
            <?php else: ?>
                <div style="margin-bottom:12px;">Для автоматического создания кампаний подключите свой Google Ads аккаунт.</div>
                <a href="<?php echo esc_url($oauth_url); ?>" class="solvera-btn">Подключить Google Ads</a>
            <?php endif; ?>
        </div>
        <div class="solvera-card" style="margin-bottom:32px;">
            <div class="solvera-title" style="margin-bottom:18px;">Интеграция с Facebook Ads</div>
            <?php 
            $fb_page_name = get_user_meta($user_id, 'solvera_facebook_page_name', true);
            ?>
            <?php if ($facebook_connected): ?>
                <div style="margin-bottom:12px;color:#43a047;font-weight:600;">Facebook Ads подключён!</div>
                <div style="margin-bottom:12px;">ID рекламного аккаунта: <b><?php echo esc_html($user_facebook_ad_account); ?></b></div>
                <?php if ($fb_page_name): ?>
                    <div style="margin-bottom:12px;">Бизнес-страница: <b><?php echo esc_html($fb_page_name); ?></b></div>
                    <form method="post" style="display:inline;">
                        <button type="submit" name="solvera_facebook_unlink_page" class="solvera-btn solvera-btn-secondary" onclick="return confirm('Отключить бизнес-страницу?');">Отключить бизнес-страницу</button>
                    </form>
                <?php endif; ?>
                <form method="post" style="display:inline;">
                    <button type="submit" name="solvera_facebook_disconnect" class="solvera-btn solvera-btn-secondary">Отключить Facebook Ads</button>
                </form>
            <?php else: ?>
                <div style="margin-bottom:12px;">Для автоматического создания кампаний подключите свой Facebook Ads аккаунт.</div>
                <a href="<?php echo esc_url($facebook_oauth_url); ?>" class="solvera-btn">Подключить Facebook Ads</a>
            <?php endif; ?>
        </div>
    </div>
    <div id="connectModalBg" class="solvera-modal-bg">
        <div class="solvera-modal">
            <button class="solvera-modal-close" onclick="closeConnectModal()">&times;</button>
            <div class="solvera-modal-title">Подключение сайта</div>
            <div id="connectModalContent"></div>
            <button class="solvera-btn" id="connectModalCheckBtn">Проверить</button>
        </div>
    </div>
    <div id="checkModalBg" class="solvera-modal-bg">
        <div class="solvera-modal">
            <button class="solvera-modal-close" onclick="closeCheckModal()">&times;</button>
            <div class="solvera-modal-title">Проверка Google Ads</div>
            <div id="checkModalContent"></div>
        </div>
    </div>
    <script>
    let sitesData = <?php
        // Pass sites data to JS for modals
        $jsSites = [];
        foreach ($sites as $site) {
            $jsSites[$site->id] = [
                'name' => $site->site_name,
                'url' => $site->site_url,
                'tracking_code' => $site->tracking_code,
                'status' => $site->status,
                'google_ads_status' => $site->google_ads_status,
            ];
        }
        echo json_encode($jsSites, JSON_UNESCAPED_UNICODE);
    ?>;

    function openConnectModal(siteId) {
        let site = sitesData[siteId];
        let html = `
            <div style="margin-bottom:10px;"><b>${site.name}</b> — <a href="${site.url}" target="_blank">${site.url}</a></div>
            <div style="margin-bottom:8px;">Вставьте этот код на все страницы вашего сайта перед тегом <b>&lt;/body&gt;</b>:</div>
            <textarea readonly onclick="this.select()">&lt;script src=&quot;https://solveraworking.jecool.net/assets/solvera-tracker.js?code=${site.tracking_code}&quot;&gt;&lt;/script&gt;</textarea>
            <div id="connectModalStatus"></div>
        `;
        document.getElementById('connectModalContent').innerHTML = html;
        document.getElementById('connectModalBg').classList.add('active');
        document.getElementById('connectModalCheckBtn').onclick = function() {
            document.getElementById('connectModalStatus').innerHTML = 'Checking...';
            // AJAX check
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                let resp;
                try { resp = JSON.parse(xhr.responseText); } catch(e) { resp = {}; }
                if (resp.success) {
                    document.getElementById('connectModalStatus').innerHTML = '<div class="solvera-modal-success">Site connected successfully!</div>';
                    setTimeout(() => { location.reload(); }, 1200);
                } else {
                    document.getElementById('connectModalStatus').innerHTML = '<div class="solvera-modal-error">' + (resp.data || 'Check error') + '</div>';
                }
            };
            xhr.send('action=solversite_check_tracking&site_id=' + siteId);
        };
    }
    function closeConnectModal() {
        document.getElementById('connectModalBg').classList.remove('active');
    }

    function openCheckModal(siteId) {
        let site = sitesData[siteId];
        document.getElementById('checkModalContent').innerHTML = 'Checking...';
        document.getElementById('checkModalBg').classList.add('active');
        // AJAX check
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            let resp;
            try { resp = JSON.parse(xhr.responseText); } catch(e) { resp = {}; }
            if (resp.success) {
                document.getElementById('checkModalContent').innerHTML = '<div class="solvera-modal-success">Site complies with Google Ads rules</div>';
            } else {
                document.getElementById('checkModalContent').innerHTML = '<div class="solvera-modal-error">' + (resp.data || 'Check error') + '</div>';
            }
        };
        xhr.send('action=solversite_check_google_ads&site_id=' + siteId);
    }

    function showErrorComment(siteId) {
        let site = sitesData[siteId];
        document.getElementById('checkModalContent').innerHTML = '<div class="solvera-modal-error">' + (site.google_ads_status || 'Violations detected') + '</div>';
        document.getElementById('checkModalBg').classList.add('active');
    }

    function closeCheckModal() {
        document.getElementById('checkModalBg').classList.remove('active');
    }
    </script>
    <?php
    return ob_get_clean();
});

// Create sites table on plugin activation
register_activation_hook(__FILE__, function() {
    global $wpdb;
    $table = $wpdb->prefix . 'solvera_websites';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        site_name VARCHAR(255) NOT NULL,
        site_url VARCHAR(255) NOT NULL,
        tracking_code VARCHAR(64) NOT NULL,
        status VARCHAR(32) DEFAULT 'pending',
        google_ads_status TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

add_action('wp_ajax_solversite_check_tracking', function() {
    $site_id = intval($_POST['site_id'] ?? 0);
    global $wpdb;
    $table = $wpdb->prefix . 'solvera_websites';
    $site = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $site_id));
    if (!$site) {
        wp_send_json_error('Site not found');
    }
    // Get site homepage
    $response = wp_remote_get($site->site_url, ['timeout' => 10]);
    if (is_wp_error($response)) {
        wp_send_json_error('Request error: ' . $response->get_error_message());
    }
    $body = wp_remote_retrieve_body($response);
    // Look for tracking code
    if (strpos($body, $site->tracking_code) !== false) {
        // Update status
        $wpdb->update($table, ['status' => 'connected'], ['id' => $site_id]);
        wp_send_json_success('Code found. Site connected!');
    } else {
        $wpdb->update($table, ['status' => 'pending'], ['id' => $site_id]);
        wp_send_json_error('Code not found on site homepage.');
    }
});

// AJAX handler for Google Ads AI check
add_action('wp_ajax_solversite_check_google_ads', function() {
    if (empty($_POST['site_id'])) {
        wp_send_json_error('Missing site_id');
    }
    $site_id = intval($_POST['site_id']);
    global $wpdb;
    $table = $wpdb->prefix . 'solvera_websites';
    $site = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $site_id));
    if (!$site) {
        wp_send_json_error('Site not found');
    }
    $response = wp_remote_get($site->site_url, ['timeout' => 15]);
    if (is_wp_error($response)) {
        wp_send_json_error('Request error: ' . $response->get_error_message());
    }
    $body = wp_remote_retrieve_body($response);

    $prompt = "Check if this page complies with Google Ads policies. Reply only OK if everything is good, or describe violations only without extra explanations:\n\n" . $body;
    $result = '';
    $use_gemini = false;

    // --- OpenAI ---
    $api_key = get_option('solvera_openai_api_key', '');
    if ($api_key) {
        $ai_response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 256,
                'temperature' => 0.2
            ])
        ]);
        if (is_wp_error($ai_response)) {
            $use_gemini = true;
        } else {
            $ai_data = json_decode(wp_remote_retrieve_body($ai_response), true);
            $result = trim($ai_data['choices'][0]['message']['content'] ?? '');
            if (!$result || stripos($result, 'error') !== false || stripos($result, 'not supported') !== false) {
                $use_gemini = true;
            }
        }
    } else {
        $use_gemini = true;
    }

    // --- Gemini ---
    if ($use_gemini) {
        $gemini_key = get_option('solvera_gemini_api_key', '');
        if (!$gemini_key) {
            $result = 'Error: no AI key available (OpenAI or Gemini).';
        } else {
            $gemini_response = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $gemini_key, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ]
                ])
            ]);
            if (is_wp_error($gemini_response)) {
                $result = 'Gemini error: ' . $gemini_response->get_error_message();
            } else {
                $gemini_data = json_decode(wp_remote_retrieve_body($gemini_response), true);
                $result = trim($gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? 'Gemini error');
            }
        }
    }

    // Save status
    $wpdb->update($table, ['google_ads_status' => $result], ['id' => $site_id]);

    // Response logic
    if (preg_match('/^ok\b/i', $result)) {
        wp_send_json_success('Checked: site complies with Google Ads policies');
    } elseif (stripos($result, 'error') !== false) {
        wp_send_json_error('Error: ' . $result);
    } else {
        wp_send_json_error('Violations found: ' . $result);
    }
});

// Handle Google Ads disconnection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solvera_google_disconnect'])) {
    delete_user_meta($user_id, 'solvera_google_ads_refresh_token');
    delete_user_meta($user_id, 'solvera_google_ads_customer_id');
    echo '<div class="solvera-alert">Google Ads disconnected.</div>';
}

// Handle Facebook Ads disconnection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solvera_facebook_disconnect'])) {
    delete_user_meta($user_id, 'solvera_facebook_access_token');
    delete_user_meta($user_id, 'solvera_facebook_ad_account');
    echo '<div class="solvera-alert">Facebook Ads disconnected.</div>';
}