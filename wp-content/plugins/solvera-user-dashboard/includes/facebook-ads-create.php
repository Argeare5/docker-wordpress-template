<?php
/*
Author: Alieinyk Liudmyla
Email: alieinyk.liudmyla@gmail.com
License: Proprietary (closed)
*/

// Facebook Ads Campaign Creation Page
add_shortcode('solvera_facebook_ads_create', function() {
    if (!is_user_logged_in()) return '<p>Пожалуйста, войдите в аккаунт.</p>';

    global $wpdb;
    $user_id = get_current_user_id();

    // Get user's business pages
    $pages = get_user_meta($user_id, 'solvera_facebook_pages', true);
    if (!$pages) {
        $page_id = get_user_meta($user_id, 'solvera_facebook_page_id', true);
        $page_name = get_user_meta($user_id, 'solvera_facebook_page_name', true);
        if ($page_id && $page_name) {
            $pages = [ ['id' => $page_id, 'name' => $page_name] ];
        }
    } else if (!is_array($pages)) {
        $pages = json_decode($pages, true);
    }

    // Get user's sites for traffic selection
    $sites = $wpdb->get_results($wpdb->prepare(
        "SELECT id, site_name, site_url FROM {$wpdb->prefix}solvera_websites WHERE user_id = %d", $user_id
    ));

    $step = 1;
    $selected_page_id = '';
    $selected_site_id = '';
    $custom_site_url = '';
    $strategy = null;
    $error = '';

    // --- Add array of available fonts ---
    $solvera_fonts = [
        'Open Sans' => __DIR__ . '/../assets/fonts/OpenSans-Bold.ttf',
        'Roboto' => __DIR__ . '/../assets/fonts/Roboto-Bold.ttf',
        'Arial' => 'arial',
        'Montserrat' => __DIR__ . '/../assets/fonts/Montserrat-Bold.ttf',
        'A SignboardCpsNr BoldItalic' => __DIR__ . '/../assets/fonts/a SignboardCpsNr BoldItalic.ttf',
        'A.D. MONO' => __DIR__ . '/../assets/fonts/A.D. MONO.ttf',
        'Abbieshire' => __DIR__ . '/../assets/fonts/Abbieshire.ttf',
        'Eras Light BT' => __DIR__ . '/../assets/fonts/Eras Light BT.ttf',
        'Free Agent Expanded Italic' => __DIR__ . '/../assets/fonts/Free Agent Expanded Italic.ttf',
        'ImperialOu Heavy Italic' => __DIR__ . '/../assets/fonts/ImperialOu Heavy Italic.ttf',
        'Insight Sans SSi' => __DIR__ . '/../assets/fonts/Insight Sans SSi.ttf',
        'ITC Century LT Book Italic' => __DIR__ . '/../assets/fonts/ITC Century LT Book Italic.ttf',
        'Ketchup Spaghetti' => __DIR__ . '/../assets/fonts/Ketchup Spaghetti.ttf',
        'KoblenzShadow Bold' => __DIR__ . '/../assets/fonts/KoblenzShadow Bold.ttf',
        'Linotype Aroma Bold' => __DIR__ . '/../assets/fonts/Linotype Aroma Bold.ttf',
        'Linotype Centennial LT 46 Light Italic' => __DIR__ . '/../assets/fonts/Linotype Centennial LT 46 Light Italic.ttf',
        'Lucida Sans Typewriter Bold Oblique' => __DIR__ . '/../assets/fonts/Lucida Sans Typewriter Bold Oblique.ttf',
        'Metroplex Shadow' => __DIR__ . '/../assets/fonts/Metroplex Shadow.ttf',
        'Monotony Italic' => __DIR__ . '/../assets/fonts/Monotony Italic.ttf',
        'MPlantin Bold' => __DIR__ . '/../assets/fonts/MPlantin Bold.ttf',
        'News 705 Italic BT' => __DIR__ . '/../assets/fonts/News 705 Italic BT.ttf',
        'Nwti' => __DIR__ . '/../assets/fonts/Nwti.ttf',
        'OctinVintageBRg Bold' => __DIR__ . '/../assets/fonts/OctinVintageBRg Bold.ttf',
        'OhitashiRg Regular' => __DIR__ . '/../assets/fonts/OhitashiRg Regular.ttf',
        'PakenhamBl Italic' => __DIR__ . '/../assets/fonts/PakenhamBl Italic.ttf',
        'PC Tennessee BoldItalic' => __DIR__ . '/../assets/fonts/PC Tennessee BoldItalic.ttf',
        'PFDinTextCondPro XThinItalic' => __DIR__ . '/../assets/fonts/PFDinTextCondPro XThinItalic.ttf',
        'Philosopher' => __DIR__ . '/../assets/fonts/Philosopher.ttf',
        'spuknik' => __DIR__ . '/../assets/fonts/spuknik.ttf',
        'Saintjohn' => __DIR__ . '/../assets/fonts/Saintjohn.ttf',
        'Thinxssk' => __DIR__ . '/../assets/fonts/Thinxssk.ttf',
        'Tiemann Light' => __DIR__ . '/../assets/fonts/Tiemann Light.ttf',
        'UFO Hunter Expanded Italic' => __DIR__ . '/../assets/fonts/UFO Hunter Expanded Italic.ttf',
        'Vijaya' => __DIR__ . '/../assets/fonts/Vijaya.ttf',
        'Vogue Normal' => __DIR__ . '/../assets/fonts/Vogue Normal.ttf',
        'Volkswagen medium' => __DIR__ . '/../assets/fonts/Volkswagen medium.ttf',
        'Vollkorn' => __DIR__ . '/../assets/fonts/Vollkorn.ttf',
        'Wedgiessk' => __DIR__ . '/../assets/fonts/Wedgiessk.ttf',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['step'])) {
            $step = intval($_POST['step']);
        }
        if (isset($_POST['choose_page'])) {
            $selected_page_id = sanitize_text_field($_POST['fb_page_id']);
            $step = 2;
        }
        if (isset($_POST['choose_site'])) {
            $selected_page_id = sanitize_text_field($_POST['fb_page_id']);
            $selected_site_id = intval($_POST['site_id']);
            $custom_site_url = esc_url_raw($_POST['custom_site_url'] ?? '');
            $step = 3;
        }
        if ($step === 3) {
            // Get data for analysis
            $selected_page_id = sanitize_text_field($_POST['fb_page_id'] ?? $selected_page_id);
            $selected_site_id = intval($_POST['site_id'] ?? $selected_site_id);
            $custom_site_url = esc_url_raw($_POST['custom_site_url'] ?? $custom_site_url);
            $site_url = '';
            if ($selected_site_id) {
                foreach ($sites as $site) {
                    if ($site->id == $selected_site_id) $site_url = $site->site_url;
                }
            } elseif ($custom_site_url) {
                $site_url = $custom_site_url;
            }
            // Get page name
            $page_name = '';
            foreach ($pages as $p) {
                if ($p['id'] == $selected_page_id) $page_name = $p['name'];
            }
            // Generate AI prompt
            $prompt = "You're a Facebook Ads expert. Analyze business page '{$page_name}'";
            if ($site_url) {
                $prompt .= " and website '{$site_url}'";
            }
            $prompt .= ". Determine:
1. Campaign objective (traffic, leads, engagement etc.)
2. Business category
3. Geography (city/region)
4. Main services/products
5. Recommended monthly budget in EUR
6. 2-3 target audiences (interests and demographics description)
7. For each audience — 2-3 creative options (headline, text, call to action, format)
8. If ads belong to a special category (credit, housing, employment, political), add field special_ad_category: 'credit'|'employment'|'housing'|'political' or 'none'.
Return result as JSON:
{
  'goal': '...',
  'category': '...',
  'geo': '...',
  'services': [...],
  'monthly_budget_eur': 500,
  'special_ad_category': 'none',
  'audiences': [ ... ]
}";
            $gemini_key = get_option('solvera_gemini_api_key', '');
            $strategy = null;
            $error = '';
            if ($gemini_key) {
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
                    $error = 'Strategy generation error: ' . $gemini_response->get_error_message();
                } else {
                    $gemini_data = json_decode(wp_remote_retrieve_body($gemini_response), true);
                    $strategy = json_decode($gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? '{}', true);
                    if (!$strategy) {
                        $error = 'Strategy generation error: failed to get data';
                    }
                }
            } else {
                $error = 'Gemini API key not set.';
            }
        }
        // --- Step 4: Upload creatives ---
        if (isset($_POST['to_upload_creatives'])) {
            $step = 4;
            $selected_page_id = sanitize_text_field($_POST['fb_page_id']);
            $selected_site_id = intval($_POST['site_id']);
            $custom_site_url = esc_url_raw($_POST['custom_site_url']);
            $goal = sanitize_text_field($_POST['goal']);
            $category = sanitize_text_field($_POST['category']);
            $geo = sanitize_text_field($_POST['geo']);
            $services = sanitize_text_field($_POST['services']);
            $monthly_budget_eur = intval($_POST['monthly_budget_eur']);
            $audiences = $_POST['audiences'] ?? [];
        }
        // --- Step 5: Campaign publishing with AI check ---
        if (isset($_POST['to_publish'])) {
            $step = 5;
            $selected_page_id = sanitize_text_field($_POST['fb_page_id']);
            $selected_site_id = intval($_POST['site_id']);
            $custom_site_url = esc_url_raw($_POST['custom_site_url']);
            $goal = sanitize_text_field($_POST['goal']);
            $category = sanitize_text_field($_POST['category']);
            $geo = sanitize_text_field($_POST['geo']);
            $services = sanitize_text_field($_POST['services']);
            $monthly_budget_eur = intval($_POST['monthly_budget_eur']);
            $audiences = $_POST['audiences'] ?? [];
            // --- AI check ---
            $ai_prompt = "Check if this creative complies with Facebook Ads policies. If there are violations - describe them. If ad belongs to special categories (credit, housing, employment, political) - specify the category. If all good - respond with OK.\n\n";
            foreach ($audiences as $aud) {
                foreach ($aud['creatives'] as $creative) {
                    $ai_prompt .= "Headline: " . ($creative['headline'] ?? '') . "\n";
                    $ai_prompt .= "Text: " . ($creative['text'] ?? '') . "\n";
                    $ai_prompt .= "Call to action: " . ($creative['cta'] ?? '') . "\n";
                }
            }
            $ai_result = '';
            $gemini_key = get_option('solvera_gemini_api_key', '');
            if ($gemini_key) {
                $gemini_response = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $gemini_key, [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode([
                        'contents' => [
                            ['parts' => [['text' => $ai_prompt]]]
                        ]
                    ])
                ]);
                if (!is_wp_error($gemini_response)) {
                    $gemini_data = json_decode(wp_remote_retrieve_body($gemini_response), true);
                    $ai_result = trim($gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? '');
                }
            }
            $status = 'draft';
            $pending_review = false;
            $fb_api_error = '';
            $fb_campaign_id = '';
            $fb_adset_id = '';
            $fb_ad_id = '';
            if (!$ai_result || stripos($ai_result, 'ok') === 0) {
                // --- Publish to Facebook via API ---
                $agent_ad_account_id = get_option('solvera_facebook_agent_ad_account_id', '');
                $user_access_token = get_user_meta($user_id, 'solvera_facebook_access_token', true);
                $fb_page_id = get_user_meta($user_id, 'solvera_facebook_page_id', true);
                $special_ad_category = $_POST['special_ad_category'] ?? 'none';
                if ($agent_ad_account_id && $user_access_token && $fb_page_id) {
                    // 1. Create Campaign
                    $campaign_data = [
                        'name' => date('Y-m-d') . '-fb-' . ($goal ?: 'ads'),
                        'objective' => 'LINK_CLICKS',
                        'status' => 'PAUSED',
                    ];
                    if ($special_ad_category !== 'none') {
                        $campaign_data['special_ad_categories'] = [strtoupper($special_ad_category)];
                    }
                    $campaign_url = 'https://graph.facebook.com/v18.0/act_' . $agent_ad_account_id . '/campaigns?access_token=' . urlencode($user_access_token);
                    $resp = wp_remote_post($campaign_url, [
                        'body' => $campaign_data
                    ]);
                    $resp_body = json_decode(wp_remote_retrieve_body($resp), true);
                    if (isset($resp_body['id'])) {
                        $fb_campaign_id = $resp_body['id'];
                        // 2. Create Ad Set
                        $adset_data = [
                            'name' => 'AdSet-' . date('Ymd-His'),
                            'campaign_id' => $fb_campaign_id,
                            'daily_budget' => max(100, intval($monthly_budget_eur * 100 / 30)), // Facebook requires budget in cents
                            'billing_event' => 'IMPRESSIONS',
                            'optimization_goal' => 'LINK_CLICKS',
                            'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
                            'status' => 'PAUSED',
                            'promoted_object' => ['page_id' => $fb_page_id],
                            'targeting' => [
                                'geo_locations' => ['countries' => ['RU']], // TODO: populate from geo
                            ]
                        ];
                        if ($special_ad_category !== 'none') {
                            $adset_data['special_ad_categories'] = [strtoupper($special_ad_category)];
                            // Limit targeting to geo only
                            unset($adset_data['targeting']['age_min']);
                            unset($adset_data['targeting']['age_max']);
                            unset($adset_data['targeting']['genders']);
                            unset($adset_data['targeting']['interests']);
                        }
                        $adset_url = 'https://graph.facebook.com/v18.0/act_' . $agent_ad_account_id . '/adsets?access_token=' . urlencode($user_access_token);
                        $resp2 = wp_remote_post($adset_url, [
                            'body' => $adset_data
                        ]);
                        $resp2_body = json_decode(wp_remote_retrieve_body($resp2), true);
                        if (isset($resp2_body['id'])) {
                            $fb_adset_id = $resp2_body['id'];
                            // 3. Upload creative (image only)
                            $creative = $audiences[0]['creatives'][0] ?? null;
                            $image_hash = '';
                            if ($creative && !empty($creative['uploaded_image_url'])) {
                                // Image must be pre-uploaded to Facebook via /act_{ad_account_id}/adimages
                                $image_hash = $creative['uploaded_image_hash'] ?? '';
                            }
                            // 4. Create Ad Creative
                            $creative_data = [
                                'name' => 'Creative-' . date('Ymd-His'),
                                'object_story_spec' => [
                                    'page_id' => $fb_page_id,
                                    'link_data' => [
                                        'image_hash' => $image_hash,
                                        'link' => $custom_site_url ?: 'https://example.com',
                                        'message' => $creative['headline'] ?? '',
                                        'caption' => $creative['cta'] ?? ''
                                    ]
                                ]
                            ];
                            $creative_url = 'https://graph.facebook.com/v18.0/act_' . $agent_ad_account_id . '/adcreatives?access_token=' . urlencode($user_access_token);
                            $resp3 = wp_remote_post($creative_url, [
                                'body' => $creative_data
                            ]);
                            $resp3_body = json_decode(wp_remote_retrieve_body($resp3), true);
                            if (isset($resp3_body['id'])) {
                                $fb_creative_id = $resp3_body['id'];
                                // 5. Create Ad
                                $ad_data = [
                                    'name' => 'Ad-' . date('Ymd-His'),
                                    'adset_id' => $fb_adset_id,
                                    'creative' => ['creative_id' => $fb_creative_id],
                                    'status' => 'PAUSED'
                                ];
                                $ad_url = 'https://graph.facebook.com/v18.0/act_' . $agent_ad_account_id . '/ads?access_token=' . urlencode($user_access_token);
                                $resp4 = wp_remote_post($ad_url, [
                                    'body' => $ad_data
                                ]);
                                $resp4_body = json_decode(wp_remote_retrieve_body($resp4), true);
                                if (isset($resp4_body['id'])) {
                                    $fb_ad_id = $resp4_body['id'];
                                    $status = 'active';
                                } else {
                                    $status = 'error';
                                    $fb_api_error = 'Error creating ad: ' . print_r($resp4_body, 1);
                                }
                            } else {
                                $status = 'error';
                                $fb_api_error = 'Error creating creative: ' . print_r($resp3_body, 1);
                            }
                        } else {
                            $status = 'error';
                            $fb_api_error = 'Error creating ad set: ' . print_r($resp2_body, 1);
                        }
                    } else {
                        $status = 'error';
                        $fb_api_error = 'Missing data for publishing (agent_ad_account_id, access_token, page_id)';
                    }
                } else {
                    $status = 'error';
                    $fb_api_error = 'Missing data for publishing (agent_ad_account_id, access_token, page_id)';
                }
            } else {
                $status = 'pending_review';
                $pending_review = true;
            }
            // Save campaign to database
            $wpdb->insert(
                $wpdb->prefix . 'solvera_campaigns',
                [
                    'user_id' => $user_id,
                    'site_id' => $selected_site_id,
                    'campaign_name' => date('Y-m-d') . '-fb-' . ($goal ?: 'ads'),
                    'campaign_type' => 'facebook',
                    'monthly_budget_eur' => $monthly_budget_eur,
                    'geo' => $geo,
                    'services' => $services,
                    'audiences' => json_encode($audiences),
                    'status' => $status,
                    'created_at' => current_time('mysql'),
                    'ai_review' => $ai_result,
                    'fb_campaign_id' => $fb_campaign_id,
                    'fb_adset_id' => $fb_adset_id,
                    'fb_ad_id' => $fb_ad_id,
                    'fb_api_error' => $fb_api_error
                ],
                ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );
            if ($wpdb->last_error) {
                $error = 'Error saving campaign: ' . $wpdb->last_error;
            } else {
                if ($status === 'active') {
                    $step = 5;
                } elseif ($pending_review) {
                    $step = 6;
                } else {
                    $step = 7;
                }
            }
        }
    }

    ob_start();
    ?>
    <div class="solvera-dashboard-content">
        <div class="solvera-card" style="max-width:1100px;">
            <?php if ($step === 1): ?>
                <div class="solvera-title" style="margin-bottom:8px;">Создать Facebook Ads кампанию</div>
                <div class="solvera-subtitle" style="margin-bottom:24px;">Шаг 1: Выберите бизнес-страницу</div>
                <?php if ($error): ?><div class="solvera-alert"><?php echo esc_html($error); ?></div><?php endif; ?>
                <form method="post" class="solvera-form">
                    <input type="hidden" name="step" value="1">
                    <label for="fb_page_id">Бизнес-страница:</label>
                    <select name="fb_page_id" id="fb_page_id" required>
                        <option value="">Выберите страницу...</option>
                        <?php foreach ($pages as $p): ?>
                            <option value="<?php echo esc_attr($p['id']); ?>" <?php if ($selected_page_id == $p['id']) echo 'selected'; ?>><?php echo esc_html($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="choose_page" class="solvera-btn" style="margin-top:16px;">Далее</button>
                </form>
            <?php elseif ($step === 2): ?>
                <div class="solvera-title" style="margin-bottom:8px;">Создать Facebook Ads кампанию</div>
                <div class="solvera-subtitle" style="margin-bottom:24px;">Шаг 2: Укажите сайт для трафика (если требуется)</div>
                <form method="post" class="solvera-form">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="fb_page_id" value="<?php echo esc_attr($selected_page_id); ?>">
                    <label for="site_id">Выберите сайт:</label>
                    <select name="site_id" id="site_id">
                        <option value="">Не указывать сайт</option>
                        <?php foreach ($sites as $site): ?>
                            <option value="<?php echo $site->id; ?>" <?php if ($selected_site_id == $site->id) echo 'selected'; ?>><?php echo esc_html($site->site_name . ' (' . $site->site_url . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin:12px 0;">или введите URL вручную:</div>
                    <input type="url" name="custom_site_url" value="<?php echo esc_attr($custom_site_url); ?>" placeholder="https://example.com" style="width:350px;">
                    <button type="submit" name="choose_site" class="solvera-btn" style="margin-top:16px;">Далее</button>
                </form>
            <?php elseif ($step === 3): ?>
                <div class="solvera-title" style="margin-bottom:8px;">Создать Facebook Ads кампанию</div>
                <div class="solvera-subtitle" style="margin-bottom:24px;">Шаг 3: Стратегия кампании</div>
                <?php if ($error): ?><div class="solvera-alert"><?php echo esc_html($error); ?></div><?php endif; ?>
                <?php if ($strategy): ?>
                    <?php $special_ad_category = $strategy['special_ad_category'] ?? 'none'; ?>
                    <?php if ($special_ad_category !== 'none'): ?>
                        <div class="solvera-alert" style="background:#fffbe6;color:#b06000;">
