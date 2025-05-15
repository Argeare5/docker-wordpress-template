<?php
// Display referral info for logged in users
add_shortcode('solvera_referrals', function() {
    if (!is_user_logged_in()) return '';

    $current_user = wp_get_current_user();
    $balance = (float) get_user_meta($current_user->ID, 'solvera_referral_balance', true);
    $count = (int) get_user_meta($current_user->ID, 'solvera_referral_paid_count', true);
    $link = home_url('/?ref=' . $current_user->ID);

    ob_start();
    ?>
    <div class="solvera-dashboard-content">
        <div class="solvera-card" style="max-width:600px;margin:0 auto;">
            <div class="solvera-title" style="margin-bottom:8px;">Реферальная программа</div>
            <div class="solvera-subtitle" style="margin-bottom:24px;">Приглашайте друзей и получайте бонусы!</div>
            <div class="solvera-metrics-row" style="display:flex;gap:24px;flex-wrap:wrap;margin-bottom:24px;">
                <div class="solvera-metric-main" style="background:#ede7f6;"><span class="solvera-metric-icon">💰</span><div><div class="solvera-metric-label">Баланс</div><div class="solvera-metric-value"><?php echo $balance; ?> €</div></div></div>
                <div class="solvera-metric-main" style="background:#e3eafc;"><span class="solvera-metric-icon">👥</span><div><div class="solvera-metric-label">Оплативших</div><div class="solvera-metric-value"><?php echo $count; ?></div></div></div>
            </div>
            <div class="solvera-referral-link-block" style="margin-bottom:18px;">
                <label class="solvera-referral-link-label" style="font-weight:600;color:#5B21B6;">Ваша реферальная ссылка:</label>
                <div style="display:flex;gap:10px;align-items:center;">
                    <input type="text" class="solvera-referral-link-input" value="<?php echo esc_attr($link); ?>" readonly onclick="this.select();" style="flex:1;min-width:0;background:#f8f6ff;border-radius:8px;border:1.5px solid #ede7f6;padding:10px 14px;font-size:1.05rem;">
                    <button type="button" class="solvera-btn" id="copyReferralBtn">Скопировать</button>
                </div>
                <div id="copyReferralMsg" style="color:#43a047;font-size:0.98rem;margin-top:6px;display:none;">Скопировано!</div>
            </div>
            <div class="solvera-referral-note" style="background:#f5f6fa;border-radius:10px;padding:14px 18px;margin-bottom:0;color:#5B21B6;font-size:1.05rem;">
                <b>Бонус 10 €</b> начисляется только после первой оплаты приглашённого пользователя.<br>
                Бонус можно использовать для оплаты рекламы или сервиса.
            </div>
            <!-- Referral table placeholder -->
            <!-- <div class="solvera-referral-table-block" style="margin-top:32px;">Referral table will be here</div> -->
        </div>
    </div>
    <script>
    document.getElementById('copyReferralBtn').onclick = function() {
        const input = document.querySelector('.solvera-referral-link-input');
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
        const msg = document.getElementById('copyReferralMsg');
        msg.style.display = 'block';
        setTimeout(() => { msg.style.display = 'none'; }, 1800);
    };
    </script>
    <?php
    return ob_get_clean();
});