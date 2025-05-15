<?php
add_shortcode('solvera_support', function() {
    if (!is_user_logged_in()) return '';
    $user = wp_get_current_user();
    $email = $user->user_email;
    $name = $user->display_name ?: $user->user_login;
    $success = $error = '';
    $subject = $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solvera_support_send'])) {
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        if (!$subject || !$message) {
            $error = 'Please fill in all fields.';
        } else {
            $to = 'support@solvera.com'; 
            $mail_subject = 'Solvera Support Request: ' . $subject;
            $mail_body = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";
            $headers = ['Content-Type: text/plain; charset=UTF-8', 'From: Solvera <no-reply@solvera.com>'];
            if (wp_mail($to, $mail_subject, $mail_body, $headers)) {
                $success = 'Your request has been sent! We will respond to your email shortly.';
                $subject = $message = '';
            } else {
                $error = 'Error sending message. Please try again later or email support@solvera.com directly.';
            }
        }
    }
    ob_start();
    ?>
    <div class="solvera-dashboard-content">
        <div class="solvera-card" style="max-width:600px;margin:0 auto;">
            <div class="solvera-title" style="margin-bottom:8px;">Техническая поддержка</div>
            <div class="solvera-subtitle" style="margin-bottom:24px;">Опишите ваш вопрос — мы ответим на e-mail в течение 1 рабочего дня.</div>
            <?php if ($success): ?><div class="solvera-alert solvera-alert-success"><?php echo esc_html($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="solvera-alert"><?php echo esc_html($error); ?></div><?php endif; ?>
            <form method="post" class="solvera-form">
                <div class="solvera-dashboard-form-group">
                    <label class="solvera-dashboard-label">Тема обращения</label>
                    <input type="text" name="subject" class="solvera-dashboard-select" value="<?php echo esc_attr($subject); ?>" required>
                </div>
                <div class="solvera-dashboard-form-group">
                    <label class="solvera-dashboard-label">Ваш e-mail</label>
                    <input type="email" class="solvera-dashboard-select" value="<?php echo esc_attr($email); ?>" readonly>
                </div>
                <div class="solvera-dashboard-form-group">
                    <label class="solvera-dashboard-label">Сообщение</label>
                    <textarea name="message" class="solvera-dashboard-select" rows="5" required><?php echo esc_textarea($message); ?></textarea>
                </div>
                <button type="submit" name="solvera_support_send" class="solvera-btn">Отправить</button>
            </form>
            <div class="solvera-referral-note" style="background:#f5f6fa;border-radius:10px;padding:14px 18px;margin-top:18px;color:#5B21B6;font-size:1.05rem;">
                Если у вас срочный вопрос, напишите на <a href="mailto:support@solvera.com" style="color:#5B21B6;text-decoration:underline;">support@solvera.com</a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});