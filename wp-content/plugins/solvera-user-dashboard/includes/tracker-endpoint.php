<?php
// Register REST endpoint for event tracking
add_action('rest_api_init', function() {
    register_rest_route('solvera/v1', '/track', [
        'methods' => 'POST',
        'callback' => 'solvera_track_event',
        'permission_callback' => '__return_true', // Public endpoint
    ]);
});

function solvera_track_event($request) {
    global $wpdb;
    $params = $request->get_json_params();
    $code = sanitize_text_field($params['code'] ?? '');
    $event = sanitize_text_field($params['event'] ?? '');
    $url = esc_url_raw($params['url'] ?? '');
    $referrer = esc_url_raw($params['referrer'] ?? '');
    $timestamp = intval($params['timestamp'] ?? time());
    $details = isset($params['details']) ? wp_json_encode($params['details']) : null;

    $table = $wpdb->prefix . 'solvera_events';
    $wpdb->insert($table, [
        'tracking_code' => $code,
        'event' => $event,
        'url' => $url,
        'referrer' => $referrer,
        'timestamp' => date('Y-m-d H:i:s', $timestamp / 1000),
        'details' => $details,
        'created_at' => current_time('mysql')
    ]);
    return ['result' => 'ok'];
}

// Create events table
add_action('init', function() {
    global $wpdb;
    $table = $wpdb->prefix . 'solvera_events';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tracking_code VARCHAR(64) NOT NULL,
        event VARCHAR(64) NOT NULL,
        url VARCHAR(255) NOT NULL,
        referrer VARCHAR(255) DEFAULT NULL,
        timestamp DATETIME NOT NULL,
        details JSON DEFAULT NULL,
        created_at DATETIME NOT NULL
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});