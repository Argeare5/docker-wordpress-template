<?php
add_shortcode('solvera_campaigns', function() {
    if (!is_user_logged_in()) return '';

    global $wpdb;
    $user_id = get_current_user_id();
    $table = $wpdb->prefix . 'solvera_campaigns';

    // --- Create campaigns table if not exists ---
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(32) NOT NULL,
        status VARCHAR(16) NOT NULL DEFAULT 'draft',
        budget DECIMAL(10,2) DEFAULT 0,
        result VARCHAR(255) DEFAULT NULL,
        result_price DECIMAL(10,2) DEFAULT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // --- Filters ---
    $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all';
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';

    $where = "WHERE user_id = $user_id";
    if ($type_filter !== 'all') {
        $where .= $wpdb->prepare(" AND type = %s", $type_filter);
    }
    if ($status_filter !== 'all') {
        $where .= $wpdb->prepare(" AND status = %s", $status_filter);
    }

    // --- Get user campaigns ---
    $campaigns = $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC");

    // --- Statistics ---
    $active_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE user_id = $user_id AND status = 'active'");

    ob_start();
    ?>
    <div class="solvera-dashboard-content">
        <div class="solvera-card solvera-campaigns-card">
            <div class="solvera-campaigns-top-row" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:18px;">
                <div class="solvera-title" style="margin-bottom:0;font-size:1.45rem;">Кампании</div>
                <div style="display:flex;gap:12px;">
                    <a href="/facebook-ads-create" class="solvera-btn solvera-btn-secondary">Facebook</a>
                    <a href="/google-ads-create" class="solvera-btn solvera-btn-primary">Google</a>
                </div>
            </div>
            <form class="solvera-form solvera-form-inline solvera-campaigns-filter" method="get">
                <label>Тип:
                    <select name="type">
                        <option value="all" <?php selected($type_filter, 'all'); ?>>Все</option>
                        <option value="google" <?php selected($type_filter, 'google'); ?>>Google</option>
                        <option value="facebook" <?php selected($type_filter, 'facebook'); ?>>Facebook</option>
                    </select>
                </label>
                <label>Статус:
                    <select name="status">
                        <option value="all" <?php selected($status_filter, 'all'); ?>>Все</option>
                        <option value="active" <?php selected($status_filter, 'active'); ?>>Активные</option>
                        <option value="draft" <?php selected($status_filter, 'draft'); ?>>Черновики</option>
                        <option value="finished" <?php selected($status_filter, 'finished'); ?>>Завершённые</option>
                    </select>
                </label>
                <button type="submit" class="solvera-btn solvera-btn-secondary">Фильтровать</button>
            </form>
            <?php if (empty($campaigns)): ?>
                <div class="solvera-campaigns-empty">
                    <div style="text-align:center;color:#888;font-size:1.1rem;">У вас пока нет кампаний</div>
                    <div style="text-align:center;margin-top:12px;">
                        <a href="/google-ads-create" class="solvera-btn solvera-btn-primary">Создать кампанию</a>
                    </div>
                </div>
            <?php else: ?>
                <table class="solvera-campaigns-table">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Тип</th>
                            <th>Бюджет</th>
                            <th>Статус</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td>
                                    <div class="solvera-campaigns-table-title"><?php echo esc_html($campaign->campaign_name); ?></div>
                                </td>
                                <td>
                                    <?php
                                    $type_class = '';
                                    $type_text = '';
                                    switch ($campaign->campaign_type) {
                                        case 'search':
                                            $type_class = 'google';
                                            $type_text = 'Google';
                                            break;
                                        case 'conversion':
                                            $type_class = 'facebook';
                                            $type_text = 'Facebook';
                                            break;
                                        default:
                                            $type_class = '';
                                            $type_text = $campaign->campaign_type;
                                    }
                                    ?>
                                    <span class="solvera-campaigns-type-badge solvera-campaigns-type-<?php echo $type_class; ?>">
                                        <?php echo esc_html($type_text); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($campaign->monthly_budget_eur); ?> €</td>
                                <td>
                                    <span class="solvera-campaigns-status-badge <?php echo esc_attr($campaign->status); ?>">
                                        <?php
                                        switch ($campaign->status) {
                                            case 'active':
                                                echo 'Активна';
                                                break;
                                            case 'paused':
                                                echo 'На паузе';
                                                break;
                                            case 'finished':
                                                echo 'Завершена';
                                                break;
                                            case 'draft':
                                                echo 'Черновик';
                                                break;
                                            default:
                                                echo esc_html($campaign->status);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($campaign->created_at)); ?></td>
                                <td>
                                    <a href="/campaigns/edit/<?php echo $campaign->id; ?>" class="solvera-btn solvera-btn-small">Редактировать</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <style>
    .solvera-switch {
        position: relative;
        display: inline-block;
        width: 38px;
        height: 22px;
    }
    .solvera-switch input {display:none;}
    .solvera-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 22px;
    }
    .solvera-slider:before {
        position: absolute;
        content: "";
        height: 16px; width: 16px;
        left: 3px; bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .solvera-slider {
        background-color: #4caf50;
    }
    input:checked + .solvera-slider:before {
        transform: translateX(16px);
    }
    input:disabled + .solvera-slider {
        background: #eee;
        cursor: not-allowed;
    }
    .solvera-btn, .solvera-btn-secondary, .solvera-btn:visited, .solvera-btn-secondary:visited {
        text-decoration: none !important;
    }
    .solvera-form-inline {
        display: flex;
        gap: 14px;
        align-items: center;
        margin-bottom: 18px;
        flex-wrap: wrap;
        background: none;
        padding: 0;
    }
    .solvera-form-inline label {
        margin-bottom: 0;
        font-weight: 500;
        color: #5B21B6;
    }
    .solvera-form-inline select {
        border: 1.5px solid #ede7f6;
        border-radius: 6px;
        padding: 6px 18px;
        font-size: 1rem;
        background: #fff;
        color: #232136;
        min-width: 100px;
        margin-left: 6px;
        height: 36px;
    }
    .solvera-form-inline .solvera-btn,
    .solvera-form-inline .solvera-btn-secondary {
        padding: 7px 18px;
        font-size: 1rem;
        height: 36px;
        margin-top: 0;
        margin-bottom: 0;
    }
    </style>
    <script>
    document.querySelectorAll('.solvera-campaign-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            var campaignId = this.getAttribute('data-campaign');
            var checked = this.checked ? 1 : 0;
            this.disabled = true;
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=solvera_toggle_campaign&campaign_id=' + campaignId + '&active=' + checked
            })
            .then(r => r.json())
            .then(resp => {
                this.disabled = false;
                if (!resp.success) alert(resp.data || 'Error!');
                else location.reload();
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
});

// --- AJAX handler for campaign status toggle ---
add_action('wp_ajax_solvera_toggle_campaign', function() {
    if (!is_user_logged_in()) wp_send_json_error('Access denied');
    $campaign_id = intval($_POST['campaign_id'] ?? 0);
    $active = intval($_POST['active'] ?? 0);

    global $wpdb;
    $user_id = get_current_user_id();
    $table = $wpdb->prefix . 'solvera_campaigns';
    $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND user_id = %d", $campaign_id, $user_id));
    if (!$campaign) wp_send_json_error('Campaign not found');

    if ($campaign->status === 'finished') {
        wp_send_json_error('Cannot modify finished campaign');
    }

    $new_status = $active ? 'active' : 'paused';
    $wpdb->update($table, ['status' => $new_status, 'updated_at' => current_time('mysql')], ['id' => $campaign_id]);
    wp_send_json_success('Status updated');
});