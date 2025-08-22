<?php
require_once 'config.php';
require_once 'includes/mail.php';

$page_title = 'Contact Us';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone'] ?? 'Not provided');
    $message = sanitize_input($_POST['message']);
    
    // Validate form data
    $errors = [];
    if (strlen($name) > 50) $errors[] = "Name must be 50 characters or less.";
    if (strlen($email) > 50) $errors[] = "Email must be 50 characters or less.";
    if (strlen($phone) > 15) $errors[] = "Phone number must be 15 characters or less.";
    if (strlen($message) > 500) $errors[] = "Message must be 500 characters or less.";
    
    if (empty($errors)) {
        // Create contact data for professional email templates
        $contact_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => 'General Inquiry',
            'message' => $message
        ];
        
        // Store contact data in session for email sending
        $_SESSION['pending_contact'] = $contact_data;
        
        // Redirect immediately for fast response
        header('Location: /thank-you?source=contact');
        exit;
    } else {
        $_SESSION['message'] = implode(' ', $errors);
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="text-center mb-5">Contact Us</h1>
            
            <div class="row mb-5">
                <div class="col-md-4 text-center mb-4">
                    <div class="contact-info">
                        <i class="fas fa-map-marker-alt fa-2x text-primary mb-3"></i>
                        <h4>Address</h4>
                        <p><?php echo get_setting('company_address', 'Address not configured'); ?></p>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="contact-info">
                        <i class="fas fa-phone fa-2x text-primary mb-3"></i>
                        <h4>Phone</h4>
                        <p><?php echo get_setting('company_phone1', 'Phone not configured'); ?><?php if(get_setting('company_phone2')): ?><br><?php echo get_setting('company_phone2'); ?><?php endif; ?></p>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="contact-info">
                        <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                        <h4>Email</h4>
                        <p><?php echo get_setting('company_email', 'Email not configured'); ?></p>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">Send us a Message</h3>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required maxlength="50">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required maxlength="50">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" maxlength="15">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required maxlength="500"></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg" id="contactSubmitBtn">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = document.getElementById('contactSubmitBtn');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    });
});
</script>

<?php include 'includes/footer.php'; ?>