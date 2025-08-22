<?php
require_once 'config.php';
require_once 'includes/mail.php';
$page_title = 'Thank You';

$source = $_GET['source'] ?? '';
if (!in_array($source, ['contact', 'quote'])) {
    header('Location: /');
    exit;
}

// Send emails asynchronously after page loads
if ($source === 'contact' && isset($_SESSION['pending_contact'])) {
    $contact_data = $_SESSION['pending_contact'];
    unset($_SESSION['pending_contact']);
    
    // Send emails in background - don't let failures break the page
    try {
        send_support_notification($contact_data);
        send_contact_thank_you($contact_data);
    } catch (Exception $e) {
        error_log('Contact email failed: ' . $e->getMessage());
    }
}

if ($source === 'quote' && isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Handle both token types
    if (strpos($token, 'lead_') === 0) {
        // Fallback token format
        $lead_id = str_replace('lead_', '', $token);
        $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$lead_id]);
        $lead = $stmt->fetch();
    } else {
        // Proper token format
        try {
            $stmt = $pdo->prepare("SELECT * FROM leads WHERE thank_you_token = ?");
            $stmt->execute([$token]);
            $lead = $stmt->fetch();
        } catch (Exception $e) {
            // Column doesn't exist, try fallback
            $lead = null;
        }
    }

    if (!$lead) {
        // Invalid token, redirect to home
        header('Location: /');
        exit;
    }

    // Check if thank you already shown (if column exists)
    try {
        if (isset($lead['thank_you_shown']) && $lead['thank_you_shown']) {
            header('Location: /');
            exit;
        }
        // Mark thank you as shown
        $stmt = $pdo->prepare("UPDATE leads SET thank_you_shown = 1 WHERE thank_you_token = ?");
        $stmt->execute([$token]);
    } catch (Exception $e) {
        // Column doesn't exist, continue without marking
    }

    // Send emails in background - don't let failures break the page
    try {
        // Send support notification
        send_lead_notification($lead);
        
        // Send customer thank you
        $formatted_date = date('F d, Y', strtotime($lead['move_date']));
        $customer_content = '
            <h2 style="color: #28a745;">Thank You for Your Quote Request!</h2>
            <p>Dear <strong>' . $lead['name'] . '</strong>,</p>
            <p>Thank you for requesting a moving quote from us. We have received your request and our team is already working on preparing a customized estimate for your move.</p>
            
            <div class="success">
                <h3>Your Moving Details</h3>
                <p><strong>Moving From:</strong> ' . $lead['move_from'] . '</p>
                <p><strong>Moving To:</strong> ' . $lead['move_to'] . '</p>
                <p><strong>Planned Move Date:</strong> ' . $formatted_date . '</p>
                <p><strong>Property Size:</strong> ' . $lead['home_size'] . '</p>
                ' . ($lead['additional_services'] ? '<p><strong>Additional Services:</strong> ' . $lead['additional_services'] . '</p>' : '') . '
            </div>
            
            <div class="highlight">
                <h3>What Happens Next?</h3>
                <ul>
                    <li>Our moving specialist will review your request</li>
                    <li>We will call you within 4 business hours</li>
                    <li>A detailed estimate will be prepared and sent to you</li>
                    <li>We can schedule a free home survey if needed</li>
                </ul>
            </div>
            
            <p>We appreciate your interest in our services and look forward to making your move smooth and hassle-free!</p>
        ';
        
        $customer_template = get_email_template('Thank You for Your Quote Request', $customer_content);
        send_email([$lead['email']], 'Your Moving Quote Request Received', $customer_template);
    } catch (Exception $e) {
        error_log('Quote email failed: ' . $e->getMessage());
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto text-center">
            <div class="card shadow-lg">
                <div class="card-body py-5">
                    <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                    
                    <?php if ($source === 'contact'): ?>
                        <h1 class="mb-4">Thank You for Contacting Us!</h1>
                        <p class="lead mb-4">We have received your message and our team will get back to you shortly.</p>
                        <p>We appreciate you taking the time to reach out to us. A member of our customer service team will review your message and respond within 24 hours.</p>
                    <?php elseif ($source === 'quote'): ?>
                        <h1 class="mb-4">Thank You for Your Quote Request!</h1>
                        <p class="lead mb-4">We have received your moving quote request and our team is preparing a customized quote for you.</p>
                        <p>Our moving specialist will review your requirements and contact you within 4 business hours to discuss your needs and provide you with a detailed quote.</p>
                    <?php endif; ?>
                    
                    <div class="mt-5">
                        <h4>What happens next?</h4>
                        <div class="row mt-4">
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-search fa-2x text-primary mb-2"></i>
                                <h6>Review</h6>
                                <small>Our team reviews your request</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                                <h6>Contact</h6>
                                <small>We'll call you to discuss details</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                                <h6>Quote</h6>
                                <small>Receive your personalized quote</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-5">
                        <a href="/" class="btn btn-primary btn-lg me-3">Back to Home</a>
                        <a href="/services" class="btn btn-outline-primary btn-lg">View Our Services</a>
                    </div>
                    
                    <div class="mt-4">
                        <p class="text-muted">
                            <small>Need immediate assistance? Call us at <strong><?php echo get_setting('company_phone1', 'Phone not configured'); ?></strong> or email <strong><?php echo get_setting('company_email', 'Email not configured'); ?></strong></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>