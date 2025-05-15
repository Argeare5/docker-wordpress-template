<?php
// Google Ads campaign creation page with site analysis and direction selection
add_shortcode('solvera_google_ads_create', function() {
    if (!is_user_logged_in()) return '<p>Пожалуйста, войдите в аккаунт.</p>';

    global $wpdb;
    $user_id = get_current_user_id();

    // Get user sites for selection
    $sites = $wpdb->get_results($wpdb->prepare(
        "SELECT id, site_name, site_url FROM {$wpdb->prefix}solvera_websites WHERE user_id = %d", $user_id
    ));

    // --- Step 0: Campaign type selection ---
    $step = 0;
    $directions = [];
    $selected_site_id = '';
    $selected_services = [];
    $error = '';
    $message = '';
    $strategy = null;

    // --- Determine current step --- 
    if (isset($_POST['step'])) {
        $step = intval($_POST['step']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choose_type'])) {
        $campaign_type = $_POST['campaign_type'] ?? 'google';
        if ($campaign_type === 'google') {
            $step = 1;
        } else {
            $step = 0; 
            $error = 'Данный тип кампании пока недоступен.';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze_site'])) {
        $selected_site_id = intval($_POST['site_id']);
        $site = null;
        foreach ($sites as $s) {
            if ($s->id == $selected_site_id) $site = $s;
        }
        if (!$site) {
            $error = 'Сайт не найден.';
        } else {
            // --- Collect content from home and key pages ---
            $site_url = rtrim($site->site_url, '/');
            $pages_to_check = [$site_url];
            $page_titles = ['услуги', 'прайс', 'о нас', 'services', 'price', 'about'];

            $main_html = wp_remote_get($site_url, ['timeout' => 15]);
            $all_text = '';
            $checked_urls = [$site_url];

            if (!is_wp_error($main_html)) {
                $body = wp_remote_retrieve_body($main_html);
                $all_text .= strip_tags($body) . "\n";

                if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $body, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $m) {
                        $href = trim(html_entity_decode($m[1]));
                        $anchor = mb_strtolower(strip_tags($m[2]));
                        foreach ($page_titles as $title) {
                            if (mb_strpos($anchor, $title) !== false || mb_strpos(mb_strtolower($href), $title) !== false) {
                                if (strpos($href, 'http') !== 0) {
                                    $href = $site_url . (substr($href, 0, 1) === '/' ? '' : '/') . $href;
                                }
                                if (!in_array($href, $checked_urls)) {
                                    $pages_to_check[] = $href;
                                    $checked_urls[] = $href;
                                }
                            }
                        }
                    }
                }
            }

            foreach ($pages_to_check as $url) {
                if ($url === $site_url) continue;
                $resp = wp_remote_get($url, ['timeout' => 12]);
                if (!is_wp_error($resp)) {
                    $all_text .= strip_tags(wp_remote_retrieve_body($resp)) . "\n";
                }
            }

            // --- Parse HTML to find services, contacts, city, phone sections ---
            $service_blocks = [];
            $contact_blocks = [];
            $geo = '';
            $phone = '';
            $country = '';
            $lang = 'en'; // English by default

            if (!is_wp_error($main_html)) {
                $body = wp_remote_retrieve_body($main_html);

                // Determine site language from <html lang="...">
                if (preg_match('/<html[^>]+lang=["\']?([a-zA-Z\-]+)["\']?/i', $body, $m)) {
                    $lang = strtolower(substr($m[1], 0, 2));
                }

                // Find sections with services/courses
                if (preg_match_all('/<(h[1-6]|div|section)[^>]*>(.*?)<\/\1>/is', $body, $matches)) {
                    foreach ($matches[0] as $block) {
                        if (preg_match('/услуг|service|course|program|программ|обучени|lesson|урок|класс|class/i', $block)) {
                            $service_blocks[] = strip_tags($block);
                        }
                        if (preg_match('/контакт|contact|address|адрес|phone|телефон/i', $block)) {
                            $contact_blocks[] = strip_tags($block);
                        }
                    }
                }

                // Find phone and city in text
                if (preg_match('/\+?\d[\d\-\s\(\)]{7,}/', $body, $m)) {
                    $phone = $m[0];
                }
                if (preg_match('/(Прага|Prague|Москва|Moscow|Санкт-Петербург|Saint Petersburg|Brno|Брно|Чехия|Czech Republic|Россия|Russia)/i', $body, $m)) {
                    $geo = $m[0];
                }
                if (preg_match('/(Чехия|Czech Republic|Россия|Russia|Украина|Ukraine|Беларусь|Belarus|Казахстан|Kazakhstan)/i', $body, $m)) {
                    $country = $m[0];
                }
            }

            // Compile analyzed text
            $analyze_text = '';
            if ($service_blocks) $analyze_text .= "SERVICES/COURSES:\n" . implode("\n", $service_blocks) . "\n";
            if ($contact_blocks) $analyze_text .= "CONTACTS:\n" . implode("\n", $contact_blocks) . "\n";
            if ($phone) $analyze_text .= "PHONE: $phone\n";
            if ($geo) $analyze_text .= "CITY: $geo\n";
            if ($country) $analyze_text .= "COUNTRY: $country\n";

            // Show analyzed text to user (can be hidden with button)
            echo '<details class="solvera-dashboard-details" style="margin:10px 0;"><summary class="solvera-dashboard-summary" style="cursor:pointer;">Показать анализируемый текст</summary>';
            echo '<pre class="solvera-dashboard-pre" style="background:#f8f8f8;color:#333;border:1px solid #ccc;padding:10px;max-width:900px;overflow:auto;">';
            echo esc_html($analyze_text);
            echo '</pre></details>';

            // --- Form prompt in required language ---
            switch ($lang) {
                case 'ru':
                    $prompt = "Проанализируй только текст ниже. Используй только то, что явно указано. Не придумывай ничего лишнего.\n"
                        . "1. Определи категорию бизнеса (одна строка, без пояснений).\n"
                        . "2. Выдели только реальные услуги или товары, которые предлагает бизнес (по одному в строке).\n"
                        . "3. Определи город и страну по контактам или адресу, если есть.\n"
                        . "4. Если не найдено — оставь поле пустым.\n"
                        . "5. Верни результат в виде JSON:\n"
                        . "{\n"
                        . "  \"category\": \"...\",\n"
                        . "  \"services\": [...],\n"
                        . "  \"geo\": [\"город\", \"страна\"],\n"
                        . "  \"phone\": \"...\"\n"
                        . "}\n"
                        . "Текст для анализа:\n";
                    break;
                    // Other languages cases unchanged...
                default:
                    $prompt = "Analyze ONLY the text below. Use ONLY what is explicitly present. Do NOT invent or add anything that is not present.\n"
                        . "1. Determine the business category (one line, no explanations).\n"
                        . "2. Extract ONLY the actual services or products offered by the business (one per line).\n"
                        . "3. Determine the city and country from the contacts or address, if present.\n"
                        . "4. If not found — leave the field empty.\n"
                        . "5. Return the result as JSON:\n"
                        . "{\n"
                        . "  \"category\": \"...\",\n"
                        . "  \"services\": [...],\n"
                        . "  \"geo\": [\"city\", \"country\"],\n"
                        . "  \"phone\": \"...\"\n"
                        . "}\n"
                        . "Text to analyze:\n";
            }

            // Add analyzed text to prompt
            $prompt .= $analyze_text;

            // --- Send API request ---
            $api_key = get_option('solvera_openai_api_key');
            if (!$api_key) {
                $error = 'API key not configured. Please contact administrator.';
            } else {
                $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode([
                        'model' => 'gpt-4',
                        'messages' => [
                            ['role' => 'system', 'content' => 'You are a helpful assistant that analyzes business websites and returns structured data.'],
                            ['role' => 'user', 'content' => $prompt]
                        ],
                        'temperature' => 0.3,
                    ]),
                    'timeout' => 30,
                ]);

                if (is_wp_error($response)) {
                    $error = 'Site analysis error: ' . $response->get_error_message();
                } else {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (isset($body['choices'][0]['message']['content'])) {
                        $result = json_decode($body['choices'][0]['message']['content'], true);
                        if ($result) {
                            $directions = $result;
                            $message = 'Analysis completed. Choose campaign directions.';
                            $step = 2;
                        } else {
                            $error = 'Failed to parse analysis result.';
                        }
                    } else {
                        $error = 'Unexpected API response format.';
                    }
                }
            }
        }
    }

    // --- "Back" button from step 2 to step 1 ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['back_to_step1'])) {
        $step = 1;
    }

    // --- Step 2: Choose directions and generate strategy ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_campaign'])) {
        $selected_site_id = intval($_POST['site_id']);
        $selected_services = isset($_POST['selected_services']) ? (array)$_POST['selected_services'] : [];
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        $geo = isset($_POST['geo']) ? $_POST['geo'] : '';
        $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
        // --- Real Gemini request ---
        $gemini_key = get_option('solvera_gemini_api_key', '');
        $prompt = "You are a Google Ads expert. Based on business category: '$category', services: '" . implode(', ', $selected_services) . "', geo: '$geo', phone: '$phone'.\n"
            . "1. Determine recommended monthly budget in EUR (e.g. 500).\n"
            . "2. Generate 30+ unique keywords and phrases.\n"
            . "3. Add negative keywords (10+ minimum).\n"
            . "4. Generate 3 headlines and 3 ad texts for each ad_group.\n"
            . "5. Return result as JSON:\n"
            . "{\n"
            . "  'campaign_type': 'search',\n"
            . "  'monthly_budget_eur': 500,\n"
            . "  'geo': '$geo',\n"
            . "  'services': [...],\n"
            . "  'keywords': [...],\n"
            . "  'negative_keywords': [...],\n"
            . "  'ad_groups': [\n"
            . "    {\n"
            . "      'name': '...',\n"
            . "      'headlines': ['...', '...', '...'],\n"
            . "      'descriptions': ['...', '...', '...']\n"
            . "    }\n"
            . "  ]\n"
            . "}\n";
            
        $response = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $gemini_key, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]),
            'timeout' => 40
        ]);
        
        $strategy = null;
        if (is_wp_error($response)) {
            $error = 'Strategy generation error: ' . $response->get_error_message();
        } else {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $text = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
            // Remove ```json ... ``` wrapper
            if (preg_match('/^```json(.*?)```$/is', $text, $m)) {
                $text = trim($m[1]);
            }
            $strategy = json_decode($text, true);
            if (!$strategy) {
                $error = 'JSON parsing error. Check generated data validity.';
            }
        }
        $step = 3;
    }

    // --- "Back" button from step 3 to step 2 ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['back_to_step2'])) {
        $step = 2;
        $selected_site_id = intval($_POST['site_id']);
        $selected_services = isset($_POST['selected_services']) ? (array)$_POST['selected_services'] : [];
        $directions['category'] = $_POST['category'] ?? '';
        $directions['geo'] = isset($_POST['geo']) ? explode(', ', $_POST['geo']) : [];
        $directions['phone'] = $_POST['phone'] ?? '';
        $directions['services'] = $selected_services;
    }

    // --- Step 3: Strategy and keywords ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_campaign'])) {
        $selected_site_id = intval($_POST['site_id']);
        $selected_services = isset($_POST['selected_services']) ? (array)$_POST['selected_services'] : [];
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        $geo = isset($_POST['geo']) ? $_POST['geo'] : '';
        $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
        $campaign_type = isset($_POST['campaign_type']) ? $_POST['campaign_type'] : 'search';
        $monthly_budget_eur = isset($_POST['monthly_budget_eur']) ? intval($_POST['monthly_budget_eur']) : 500;
        $keywords = isset($_POST['keywords']) ? explode(',', $_POST['keywords']) : [];
        $negative_keywords = isset($_POST['negative_keywords']) ? explode(',', $_POST['negative_keywords']) : [];
        $ad_groups = [];
        if (isset($_POST['ad_groups']) && is_array($_POST['ad_groups'])) {
            foreach ($_POST['ad_groups'] as $group) {
                $ad_groups[] = [
                    'name' => $group['name'] ?? '',
                    'headlines' => $group['headlines'] ?? [],
                    'descriptions' => $group['descriptions'] ?? []
                ];
            }
        }
        // --- Form campaign name ---
        $date = date('Y-m-d');
        $service = isset($selected_services[0]) ? $selected_services[0] : '';
        $campaign_name = $date . '-' . $campaign_type . '-' . $service;
        // --- Save to database ---
        $wpdb->insert(
            $wpdb->prefix . 'solvera_campaigns',
            [
                'user_id' => get_current_user_id(),
                'site_id' => $selected_site_id, 
                'campaign_name' => $campaign_name,
                'campaign_type' => $campaign_type,
                'monthly_budget_eur' => $monthly_budget_eur,
                'geo' => $geo,
                'services' => json_encode($selected_services),
                'keywords' => json_encode($keywords),
                'negative_keywords' => json_encode($negative_keywords),
                'ad_groups' => json_encode($ad_groups),
                'status' => 'draft',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        if ($wpdb->last_error) {
            $error = 'Campaign save error: ' . $wpdb->last_error;
        } else {
            $message = 'Campaign saved successfully!';
            wp_redirect('/campaigns');
            exit;
        }
    }

    ob_start();
    ?>
    <div class="solvera-dashboard-content">
        <div class="solvera-card" style="max-width:1100px;">
            <?php if ($step === 0): ?>
                <div class="solvera-title" style="margin-bottom:8px;">Создать рекламную кампанию</div>
                <div class="solvera-subtitle" style="margin-bottom:24px;">Выберите тип кампании</div>
                <?php if (!empty($error)): ?>
                    <div class="solvera-alert"><?php echo esc_html($error); ?></div>
                <?php endif; ?>
                <form method="post" class="solvera-form" style="display:flex;gap:32px;flex-wrap:wrap;justify-content:center;">
                    <button type="submit" name="choose_type" value="1" class="solvera-campaign-type-card" style="background:#ede7f6;border:none;border-radius:16px;padding:36px 48px;min-width:260px;cursor:pointer;box-shadow:0 2px 12px rgba(80,41,186,0.04);font-size:1.15rem;font-weight:600;color:#5B21B6;transition:box-shadow 0.2s;">
                        <input type="hidden" name="campaign_type" value="google">
                        <span style="font-size:2.2rem;display:block;margin-bottom:12px;">🔎</span>
                        Google реклама
                    </button>
                    <div class="solvera-campaign-type-card" style="background:#f5f6fa;border:none;border-radius:16px;padding:36px 48px;min-width:260px;opacity:0.5;cursor:not-allowed;box-shadow:0 2px 12px rgba(80,41,186,0.04);font-size:1.15rem;font-weight:600;color:#888;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                        <span style="font-size:2.2rem;display:block;margin-bottom:12px;">📘</span>
                        Facebook реклама
                        <span style="margin-top:10px;font-size:0.98rem;color:#bdbdbd;">Скоро</span>
                    </div>
                    <div class="solvera-campaign-type-card" style="background:#f5f6fa;border:none;border-radius:16px;padding:36px 48px;min-width:260px;opacity:0.5;cursor:not-allowed;box-shadow:0 2px 12px rgba(80,41,186,0.04);font-size:1.15rem;font-weight:600;color:#888;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                        <span style="font-size:2.2rem;display:block;margin-bottom:12px;">🔎+📘</span>
                        Google + Facebook
                        <span style="margin-top:10px;font-size:0.98rem;color:#bdbdbd;">Скоро</span>
                    </div>
                </form>
            <?php elseif ($step === 1): ?>
                <div class="solvera-title" style="margin-bottom:8px;">Создание Google Ads кампании</div>
                <div class="solvera-subtitle" style="margin-bottom:24px;">Шаг 1: Выбор сайта и анализ</div>
                <form method="post" class="solvera-form">
                    <input type="hidden" name="step" value="1">
                    <label for="site_id">Выберите сайт для анализа:</label>
                    <select name="site_id" id="site_id" required>
                        <option value="">Выберите сайт...</option>
                        <?php foreach ($sites as $site):
                            $selected = $selected_site_id == $site->id ? 'selected' : '';
                        ?>
                            <option value="<?php echo $site->id; ?>" <?php echo $selected; ?>><?php echo esc_html($site->site_name . ' (' . $site->site_url . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="analyze_site" class="solvera-btn" style="margin-top:10px;">Анализировать</button>
                </form>
                <?php if ($error): ?>
                    <div class="solvera-alert"><?php echo esc_html($error); ?></div>
                <?php endif; ?>
                <?php if ($message): ?>
                    <div class="solvera-alert solvera-alert-success"><?php echo esc_html($message); ?></div>
                <?php endif; ?>
            <?php elseif ($step === 2): ?>
                <div class="solvera-title" style="margin-bottom:8px;">Создание Google Ads кампании</div>
                <div class="solvera-subtitle" style="margin-bottom:24px;">Шаг 2: Выбор направлений для рекламы</div>
                <form method="post" class="solvera-form">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="site_id" value="<?php echo esc_attr($selected_site_id); ?>">
                    <input type="hidden" name="category" value="<?php echo esc_attr($directions['category'] ?? ''); ?>">
                    <input type="hidden" name="geo" value="<?php echo esc_attr(isset($directions['geo']) ? implode(', ', $directions['geo']) : ''); ?>">
                    <input type="hidden" name="phone" value="<?php echo esc_attr($directions['phone'] ?? ''); ?>">
                    <div style="margin-bottom:12px;"><b>Категория:</b><br><?php echo esc_html($directions['category'] ?? ''); ?></div>
                    <div style="margin-bottom:12px;"><b>Услуги/товары:</b><br>
                        <?php if (!empty($directions['services'])): foreach ($directions['services'] as $service): ?>
                            <label style="display:inline-block;margin:0 12px 8px 0;">
                                <input type="checkbox" name="selected_services[]" value="<?php echo esc_attr($service); ?>" checked>
                                <?php echo esc_html($service); ?>
                            </label>
                        <?php endforeach; endif; ?>
                    </div>
                    <div style="margin-bottom:12px;"><b>География:</b><br><?php echo esc_html(isset($directions['geo']) ? implode(', ', $directions['geo']) : ''); ?></div>
                    <div style="margin-bottom:18px;"><b>Телефон:</b><br><?php echo esc_html($directions['phone'] ?? ''); ?></div>
                    <button type="submit" name="create_campaign" class="solvera-btn">Продолжить</button>
                    <button type="submit" name="back_to_step1" class="solvera-btn solvera-btn-secondary" style="margin-left:10px;">Назад</button>
                </form>
            <?php elseif ($step === 3): ?>
                <div class="solvera-title" style="margin-bottom:8px;">Создание Google Ads кампании</div>
                <div class="solvera-subtitle" style="margin-bottom:24px;">Шаг 3: Стратегия и ключевые слова</div>
                <?php if ($error): ?>
                    <div class="solvera-alert"><?php echo esc_html($error); ?></div>
                <?php elseif ($strategy): ?>
                    <form method="post" class="solvera-form">
                        <input type="hidden" name="step" value="3">
                        <input type="hidden" name="site_id" value="<?php echo esc_attr($selected_site_id); ?>">
                        <input type="hidden" name="category" value="<?php echo esc_attr($category ?? ''); ?>">
                        <input type="hidden" name="geo" value="<?php echo esc_attr($geo ?? ''); ?>">
                        <input type="hidden" name="phone" value="<?php echo esc_attr($phone ?? ''); ?>">
                        <?php if (!empty($selected_services)): foreach ($selected_services as $service): ?>
                            <input type="hidden" name="selected_services[]" value="<?php echo esc_attr($service); ?>">
                        <?php endforeach; endif; ?>
                        <div style="margin-bottom:18px;">
                            <label for="campaign_type"><b>Тип кампании:</b></label>
                            <input type="text" id="campaign_type" name="campaign_type" value="<?php echo esc_attr($strategy['campaign_type']); ?>" class="solvera-input">
                        </div>
                        <div style="margin-bottom:18px;">
                            <label for="monthly_budget_eur"><b>Бюджет (€):</b></label>
                            <input type="number" id="monthly_budget_eur" name="monthly_budget_eur" value="<?php echo esc_attr($strategy['monthly_budget_eur']); ?>" class="solvera-input">
                        </div>
                        <div style="margin-bottom:18px;">
                            <label for="keywords"><b>Ключевые слова:</b></label>
                            <textarea id="keywords" name="keywords" class="solvera-input" rows="3"><?php echo esc_textarea(implode(', ', $strategy['keywords'])); ?></textarea>
                        </div>
                        <div style="margin-bottom:18px;">
                            <label for="negative_keywords"><b>Минус-слова:</b></label>
                            <textarea id="negative_keywords" name="negative_keywords" class="solvera-input" rows="3"><?php echo esc_textarea(implode(', ', $strategy['negative_keywords'] ?? [])); ?></textarea>
                        </div>
                        <div style="margin-bottom:18px;"><b>Группы объявлений:</b><br>
                            <?php foreach ($strategy['ad_groups'] as $index => $group): ?>
                                <div style="margin-bottom:10px;">
                                    <input type="text" name="ad_groups[<?php echo $index; ?>][name]" value="<?php echo esc_attr($group['name']); ?>" class="solvera-input" placeholder="Название группы">
                                    <div style="margin-top:5px;">
                                        <label>Заголовки:</label>
                                        <textarea name="ad_groups[<?php echo $index; ?>][headlines]" class="solvera-input" rows="2"><?php echo esc_textarea(implode(', ', $group['headlines'])); ?></textarea>
                                    </div>
                                    <div style="margin-top:5px;">
                                        <label>Описания:</label>
                                        <textarea name="ad_groups[<?php echo $index; ?>][descriptions]" class="solvera-input" rows="2"><?php echo esc_textarea(implode(', ', $group['descriptions'])); ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" name="publish_campaign" class="solvera-btn">Опубликовать</button>
                        <button type="submit" name="back_to_step2" class="solvera-btn solvera-btn-secondary" style="margin-left:10px;">Назад</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

// --- Auto add columns to campaigns table ---
add_action('init', function() {
    global $wpdb;
    $table = $wpdb->prefix . 'solvera_campaigns';
    $fields = [
        'user_id' => 'INT(11) DEFAULT NULL',
        'site_id' => 'INT(11) DEFAULT NULL', 
        'campaign_name' => 'VARCHAR(255) DEFAULT NULL',
        'campaign_type' => "VARCHAR(32) DEFAULT 'search'",
        'monthly_budget_eur' => 'INT(11) DEFAULT NULL',
        'geo' => 'VARCHAR(255) DEFAULT NULL',
        'services' => 'TEXT DEFAULT NULL',
        'keywords' => 'TEXT DEFAULT NULL',
        'negative_keywords' => 'TEXT DEFAULT NULL',
        'ad_groups' => 'LONGTEXT DEFAULT NULL',
        'status' => "VARCHAR(32) DEFAULT 'draft'",
        'created_at' => 'DATETIME DEFAULT NULL',
    ];
