<?php
add_shortcode('solvera_analytics', function() {
    if (!is_user_logged_in()) return '';

    global $wpdb; 
    $user_id = get_current_user_id();

    // Get tracking codes for all user's websites
    $websites = $wpdb->get_col($wpdb->prepare(
        "SELECT tracking_code FROM {$wpdb->prefix}solvera_websites WHERE user_id = %d", $user_id
    ));
    if (!$websites) return '<p>Нет подключённых сайтов.</p>';

    $codes_in = "'" . implode("','", array_map('esc_sql', $websites)) . "'";

    // Default dates: last 7 days
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-6 days'));
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

    // Get events by day
    $events_table = $wpdb->prefix . 'solvera_events';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(timestamp) as day, 
            SUM(event = 'click') as clicks,
            SUM(event = 'impression') as impressions,
            SUM(event = 'purchase') as conversions,
            SUM(CASE WHEN event = 'purchase' THEN CAST(details->>'$.amount' AS DECIMAL(10,2)) ELSE 0 END) as spend
        FROM $events_table
        WHERE tracking_code IN ($codes_in)
          AND DATE(timestamp) BETWEEN %s AND %s
        GROUP BY day
        ORDER BY day ASC",
        $date_from, $date_to
    ));

    // Prepare chart data
    $labels = [];
    $clicks = [];
    $impressions = [];
    $conversions = [];
    $spend = [];
    $cpa = [];
    foreach ($rows as $row) {
        $labels[] = $row->day;
        $clicks[] = (int)$row->clicks;
        $impressions[] = (int)$row->impressions;
        $conversions[] = (int)$row->conversions; 
        $spend[] = (float)$row->spend;
        $cpa[] = $row->conversions ? round($row->spend / $row->conversions, 2) : 0;
    }

    // Calculate period metrics
    $total_clicks = array_sum($clicks);
    $total_impressions = array_sum($impressions);
    $total_conversions = array_sum($conversions);
    $total_spend = array_sum($spend);
    $ctr = $total_impressions ? round($total_clicks / $total_impressions * 100, 2) : 0;
    $cpa_total = $total_conversions ? round($total_spend / $total_conversions, 2) : 0;

    ob_start();
    ?>
    <div class="solvera-dashboard-content">
        <div class="solvera-card">
            <div class="solvera-title">Аналитика</div>
            <form method="get" class="solvera-form solvera-analytics-filter" style="margin-bottom:28px;display:flex;gap:18px;flex-wrap:wrap;align-items:end;">
                <input type="hidden" name="page_id" value="<?php echo get_queried_object_id(); ?>">
                <div>
                    <label style="font-weight:600;color:#5B21B6;">С:</label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="solvera-input">
                </div>
                <div>
                    <label style="font-weight:600;color:#5B21B6;">По:</label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="solvera-input">
                </div>
                <button type="submit" class="solvera-btn">Показать</button>
            </form>
            <?php if ($rows): ?>
            <div class="solvera-metrics-row" style="display:flex;gap:28px;flex-wrap:wrap;margin-bottom:32px;">
                <div class="solvera-metric-main" style="background:#ede7f6;"><span class="solvera-metric-icon">👆</span><div><div class="solvera-metric-label">Клики</div><div class="solvera-metric-value"><?php echo $total_clicks; ?></div></div></div>
                <div class="solvera-metric-main" style="background:#e3eafc;"><span class="solvera-metric-icon">👁️</span><div><div class="solvera-metric-label">Показы</div><div class="solvera-metric-value"><?php echo $total_impressions; ?></div></div></div>
                <div class="solvera-metric-main" style="background:#e8f5e9;"><span class="solvera-metric-icon">🎯</span><div><div class="solvera-metric-label">Конверсии</div><div class="solvera-metric-value"><?php echo $total_conversions; ?></div></div></div>
                <div class="solvera-metric-main" style="background:#ede7f6;"><span class="solvera-metric-icon">📈</span><div><div class="solvera-metric-label">CTR</div><div class="solvera-metric-value"><?php echo $ctr; ?>%</div></div></div>
                <div class="solvera-metric-main" style="background:#f5f6fa;"><span class="solvera-metric-icon">💸</span><div><div class="solvera-metric-label">CPA</div><div class="solvera-metric-value"><?php echo $cpa_total; ?> €</div></div></div>
                <div class="solvera-metric-main" style="background:#f8f6ff;"><span class="solvera-metric-icon">💰</span><div><div class="solvera-metric-label">Расходы</div><div class="solvera-metric-value"><?php echo $total_spend; ?> €</div></div></div>
            </div>
            <div class="solvera-analytics-charts" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:32px;">
                <div class="solvera-analytics-chart-block">
                    <h4 style="color:#5B21B6;font-weight:700;margin-bottom:10px;">Клики</h4>
                    <canvas id="solveraClicksChart" height="60"></canvas>
                </div>
                <div class="solvera-analytics-chart-block">
                    <h4 style="color:#5B21B6;font-weight:700;margin-bottom:10px;">Показы</h4>
                    <canvas id="solveraImpressionsChart" height="60"></canvas>
                </div>
                <div class="solvera-analytics-chart-block">
                    <h4 style="color:#5B21B6;font-weight:700;margin-bottom:10px;">Конверсии</h4>
                    <canvas id="solveraConversionsChart" height="60"></canvas>
                </div>
                <div class="solvera-analytics-chart-block">
                    <h4 style="color:#5B21B6;font-weight:700;margin-bottom:10px;">CPA (Цена за конверсию)</h4>
                    <canvas id="solveraCpaChart" height="60"></canvas>
                </div>
                <div class="solvera-analytics-chart-block">
                    <h4 style="color:#5B21B6;font-weight:700;margin-bottom:10px;">Расходы</h4>
                    <canvas id="solveraSpendChart" height="60"></canvas>
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            const labels = <?php echo json_encode($labels); ?>;
            new Chart(document.getElementById('solveraClicksChart').getContext('2d'), {
                type: 'line',
                data: { labels: labels, datasets: [{ label: 'Клики', data: <?php echo json_encode($clicks); ?>, borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,0.08)', fill: true, tension:0.3 }] },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: {display:false}}, y: { grid: {color:'#ede7f6'} } } }
            });
            new Chart(document.getElementById('solveraImpressionsChart').getContext('2d'), {
                type: 'line',
                data: { labels: labels, datasets: [{ label: 'Показы', data: <?php echo json_encode($impressions); ?>, borderColor: '#5B21B6', backgroundColor: 'rgba(93,33,182,0.08)', fill: true, tension:0.3 }] },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: {display:false}}, y: { grid: {color:'#ede7f6'} } } }
            });
            new Chart(document.getElementById('solveraConversionsChart').getContext('2d'), {
                type: 'line',
                data: { labels: labels, datasets: [{ label: 'Конверсии', data: <?php echo json_encode($conversions); ?>, borderColor: '#43a047', backgroundColor: 'rgba(67,160,71,0.08)', fill: true, tension:0.3 }] },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: {display:false}}, y: { grid: {color:'#ede7f6'} } } }
            });
            new Chart(document.getElementById('solveraCpaChart').getContext('2d'), {
                type: 'line',
                data: { labels: labels, datasets: [{ label: 'CPA', data: <?php echo json_encode($cpa); ?>, borderColor: '#fbc02d', backgroundColor: 'rgba(251,192,45,0.08)', fill: true, tension:0.3 }] },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: {display:false}}, y: { grid: {color:'#ede7f6'} } } }
            });
            new Chart(document.getElementById('solveraSpendChart').getContext('2d'), {
                type: 'line',
                data: { labels: labels, datasets: [{ label: 'Расходы', data: <?php echo json_encode($spend); ?>, borderColor: '#e53935', backgroundColor: 'rgba(229,57,53,0.08)', fill: true, tension:0.3 }] },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: {display:false}}, y: { grid: {color:'#ede7f6'} } } }
            });
            </script>
            <?php else: ?>
            <div class="solvera-empty-state" style="padding:60px 0 40px 0;">
                <div class="solvera-empty-icon" style="font-size:3.2rem;margin-bottom:18px;">📊</div>
                <div class="solvera-empty-title" style="font-size:1.25rem;margin-bottom:12px;color:#5B21B6;">Нет данных для выбранного периода</div>
                <div class="solvera-empty-text">Попробуйте выбрать другие даты или подключить сайт к Solvera.</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}); 