<?php
require_once 'config.php';
require_once 'includes/mail.php';
require_login();
require_admin();

$page_title = 'Test Email Configuration';

if ($_POST['test_email'] ?? false) {
    $test_email = sanitize_input($_POST['test_email']);
    
    if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $test_content = '
            <h2 style="color: #28a745;">Email Configuration Test</h2>
            <p>This is a test email to verify your SMTP configuration is working correctly.</p>
            
            <div class="highlight">
                <h3>Test Details</h3>
                <p><strong>Sent At:</strong> ' . date('F d, Y H:i:s') . '</p>
                <p><strong>From:</strong> ' . get_setting('mail_from', 'Not configured') . '</p>
                <p><strong>SMTP Host:</strong> ' . get_setting('mail_host', 'Not configured') . '</p>
                <p><strong>SMTP Port:</strong> ' . get_setting('mail_port', '587') . '</p>
            </div>
            
            <p>If you received this email, your email configuration is working properly!</p>
        ';
        
        $email_template = get_email_template('Email Configuration Test', $test_content);
        $result = send_email([$test_email], 'Email Configuration Test', $email_template);
        
        if ($result) {
            $message = "✅ Test email sent successfully to $test_email";
            $message_type = "success";
        } else {
            $message = "❌ Failed to send test email. Check your SMTP settings.";
            $message_type = "danger";
        }
    } else {
        $message = "❌ Please enter a valid email address.";
        $message_type = "danger";
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="mb-4">Test Email Configuration</h1>
            
            <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="card shadow">
                <div class="card-body">
                    <h3>Current Email Settings</h3>
                    <table class="table">
                        <tr>
                            <td><strong>SMTP Host:</strong></td>
                            <td><?php echo get_setting('mail_host', 'Not configured'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>SMTP Port:</strong></td>
                            <td><?php echo get_setting('mail_port', '587'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Username:</strong></td>
                            <td><?php echo get_setting('mail_username', 'Not configured'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>From Email:</strong></td>
                            <td><?php echo get_setting('mail_from', 'Not configured'); ?></td>
                        </tr>
                    </table>
                    
                    <hr>
                    
                    <h3>Send Test Email</h3>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="test_email" class="form-label">Test Email Address</label>
                            <input type="email" class="form-control" id="test_email" name="test_email" required placeholder="Enter email to test">
                        </div>
                        <button type="submit" class="btn btn-primary">Send Test Email</button>
                        <a href="/settings" class="btn btn-secondary">Back to Settings</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>