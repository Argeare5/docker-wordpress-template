<?php
add_shortcode('solvera_ai_assistant', function() {
    if (!is_user_logged_in()) return '';
    ob_start();
    ?>
    <div class="solvera-dashboard-content">
        <div class="solvera-card solvera-ai-card">
            <div class="solvera-ai-header">
                <div class="solvera-ai-icon">🤖</div>
                <div>
                    <div class="solvera-ai-title">AI-ассистент</div>
                    <div class="solvera-ai-subtitle">Ваш персональный помощник для рекламы и аналитики</div>
                </div>
            </div>
            <div class="solvera-ai-placeholder">
                <div class="solvera-ai-illustration">💡</div>
                <div class="solvera-ai-placeholder-title">Раздел в разработке</div>
                <div class="solvera-ai-placeholder-text">Скоро здесь появится умный помощник для ваших рекламных кампаний, аналитики и автоматизации.</div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}); 