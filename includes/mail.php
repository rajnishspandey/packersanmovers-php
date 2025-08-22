<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Professional email template function
function get_email_template($title, $content, $footer_note = '') {
    $company_name = get_setting('company_name', 'PackersAnMovers');
    $company_tagline = get_setting('company_tagline', '');
    $company_phone1 = get_setting('company_phone1', '');
    $company_phone2 = get_setting('company_phone2', '');
    $company_email = get_setting('company_email', '');
    $company_website = get_setting('company_website', '');
    $company_address = get_setting('company_address', '');
    $facebook_url = get_setting('facebook_url', '');
    $twitter_url = get_setting('twitter_url', '');
    $instagram_url = get_setting('instagram_url', '');
    $linkedin_url = get_setting('linkedin_url', '');
    
    $contact_info = [];
    if ($company_phone1) $contact_info[] = '<i class="fas fa-phone"></i> ' . $company_phone1;
    if ($company_phone2) $contact_info[] = '<i class="fas fa-phone"></i> ' . $company_phone2;
    if ($company_email) $contact_info[] = '<i class="fas fa-envelope"></i> ' . $company_email;
    if ($company_website) $contact_info[] = '<i class="fas fa-globe"></i> <a href="' . $company_website . '" style="color: #007bff;">' . $company_website . '</a>';
    
    $social_links = [];
    if ($facebook_url) $social_links[] = '<a href="' . $facebook_url . '" style="color: #3b5998; text-decoration: none; margin-right: 10px;"><i class="fab fa-facebook"></i> Facebook</a>';
    if ($twitter_url) $social_links[] = '<a href="' . $twitter_url . '" style="color: #1da1f2; text-decoration: none; margin-right: 10px;"><i class="fab fa-twitter"></i> Twitter</a>';
    if ($instagram_url) $social_links[] = '<a href="' . $instagram_url . '" style="color: #e4405f; text-decoration: none; margin-right: 10px;"><i class="fab fa-instagram"></i> Instagram</a>';
    if ($linkedin_url) $social_links[] = '<a href="' . $linkedin_url . '" style="color: #0077b5; text-decoration: none; margin-right: 10px;"><i class="fab fa-linkedin"></i> LinkedIn</a>';
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $title . '</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .footer { background: #f8f9fa; padding: 20px; border-top: 1px solid #dee2e6; }
            .contact-info { margin: 15px 0; }
            .contact-info div { margin: 5px 0; }
            .social-links { margin: 15px 0; }
            .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            .highlight { background: #e3f2fd; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0; }
            .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; }
            .success { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1 style="margin: 0; font-size: 32px; font-weight: bold;">' . $company_name . '</h1>
                <p style="margin: 10px 0 0 0; opacity: 0.9; font-size: 16px;">Professional Packers & Movers</p>
                ' . ($company_tagline ? '<p style="margin: 5px 0 0 0; opacity: 0.7; font-size: 12px;">' . $company_tagline . '</p>' : '') . '
            </div>
            <div class="content">
                ' . $content . '
            </div>
            <div class="footer">
                <div style="text-align: center;">
                    <h4 style="color: #007bff; margin-bottom: 15px;">Contact Information</h4>
                    <div class="contact-info">
                        ' . implode('<br>', $contact_info) . '
                    </div>
                    ' . ($company_address ? '<div style="margin: 15px 0;"><i class="fas fa-map-marker-alt"></i> ' . $company_address . '</div>' : '') . '
                    ' . ($social_links ? '<div class="social-links">' . implode(' ', $social_links) . '</div>' : '') . '
                    ' . ($footer_note ? '<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d;">' . $footer_note . '</div>' : '') . '
                    <div style="margin-top: 15px; font-size: 12px; color: #6c757d;">
                        This email was sent by ' . $company_name . '. Please do not reply to this automated message.
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
}

function send_email($recipients, $subject, $body) {
    // Convert recipients to array if it's a string
    if (!is_array($recipients)) {
        $recipients = [$recipients];
    }
    
    // Check if we have valid recipients
    $valid_recipients = array_filter($recipients, function($email) {
        return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
    });
    
    if (empty($valid_recipients)) {
        error_log("No valid email recipients provided");
        return false;
    }
    
    // Check if email is properly configured
    if (empty(MAIL_USERNAME) || empty(MAIL_HOST)) {
        error_log("Email not configured - please configure email settings");
        return false;
    }
    
    // Try PHPMailer first if available
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        
        try {
            $mail = new PHPMailer(true);
            
            // SMTP configuration with security and timeout
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = (MAIL_PORT == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = MAIL_PORT;
            $mail->Timeout = 5; // 5 second timeout for faster response
            $mail->SMTPKeepAlive = false;
            $mail->SMTPDebug = 0; // Disable debug for production
            
            $mail->setFrom(MAIL_FROM, get_setting('company_name', 'PackersAnMovers'));
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            foreach ($valid_recipients as $recipient) {
                $mail->addAddress($recipient);
            }
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    // Fallback to basic PHP mail with timeout
    ini_set('default_socket_timeout', 5);
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . MAIL_FROM . "\r\n";
    
    $success = true;
    foreach ($valid_recipients as $recipient) {
        if (!mail($recipient, $subject, $body, $headers)) {
            $success = false;
            error_log("Failed to send email to: " . $recipient);
        }
    }
    
    return $success;
}

// Professional email templates for different business processes
function send_lead_notification($lead_data) {
    $content = '
        <h2 style="color: #007bff;">New Lead Inquiry Received</h2>
        <div class="highlight">
            <h3>Customer Details</h3>
            <p><strong>Name:</strong> ' . $lead_data['name'] . '</p>
            <p><strong>Email:</strong> ' . $lead_data['email'] . '</p>
            <p><strong>Phone:</strong> ' . $lead_data['phone'] . '</p>
        </div>
        <div class="highlight">
            <h3>Moving Details</h3>
            <p><strong>From:</strong> ' . $lead_data['move_from'] . '</p>
            <p><strong>To:</strong> ' . $lead_data['move_to'] . '</p>
            <p><strong>Date:</strong> ' . date('M d, Y', strtotime($lead_data['move_date'])) . '</p>
            <p><strong>Home Size:</strong> ' . $lead_data['home_size'] . '</p>
            ' . ($lead_data['additional_services'] ? '<p><strong>Additional Services:</strong> ' . $lead_data['additional_services'] . '</p>' : '') . '
            ' . ($lead_data['notes'] ? '<p><strong>Notes:</strong> ' . $lead_data['notes'] . '</p>' : '') . '
        </div>
        <p><strong>Action Required:</strong> Please contact the customer within 2 hours to schedule a survey.</p>
    ';
    
    $template = get_email_template('New Lead Inquiry - ' . $lead_data['name'], $content);
    return send_email(get_support_emails(), 'New Lead Inquiry - ' . $lead_data['name'], $template);
}

function send_quote_email($quote_data, $lead_data, $type = 'new') {
    $action_text = [
        'new' => 'Your Moving Estimate is Ready!',
        'updated' => 'Your Moving Estimate has been Updated!',
        'sent' => 'Your Moving Estimate - Ready for Review'
    ];
    
    $content = '
        <h2 style="color: #28a745;">' . $action_text[$type] . '</h2>
        <p>Dear <strong>' . $lead_data['name'] . '</strong>,</p>
        <p>Thank you for choosing us for your moving needs. We have prepared a detailed estimate for your relocation.</p>
        
        <div class="success">
            <h3>Estimate Details</h3>
            <p><strong>Estimate Number:</strong> ' . $quote_data['quote_number'] . '</p>
            <p><strong>Total Amount:</strong> Rs ' . number_format($quote_data['total_amount'], 2) . '</p>
            <p><strong>Valid Until:</strong> ' . date('M d, Y', strtotime($quote_data['valid_until'])) . '</p>
        </div>
        
        <div class="highlight">
            <h3>Moving Details</h3>
            <p><strong>From:</strong> ' . $lead_data['move_from'] . '</p>
            <p><strong>To:</strong> ' . $lead_data['move_to'] . '</p>
            <p><strong>Preferred Date:</strong> ' . date('M d, Y', strtotime($lead_data['move_date'])) . '</p>
            <p><strong>Property Size:</strong> ' . $lead_data['home_size'] . '</p>
        </div>
        
        <p>Our estimate includes professional packing, safe transportation, and careful handling of your belongings. All our services come with insurance coverage for your peace of mind.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . get_setting('website_url', '#') . '/estimate?token=' . $quote_data['secure_token'] . '" class="btn">View Detailed Estimate</a>
        </div>
        
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>Review the detailed estimate using the link above</li>
            <li>Contact us if you have any questions or need modifications</li>
            <li>Confirm your booking to secure your moving date</li>
        </ul>
        
        <p>We look forward to making your move smooth and hassle-free!</p>
    ';
    
    $footer_note = 'This estimate is valid until ' . date('M d, Y', strtotime($quote_data['valid_until'])) . '. Prices may vary based on actual inventory and additional services.';
    $template = get_email_template('Moving Estimate - ' . $quote_data['quote_number'], $content, $footer_note);
    return send_email([$lead_data['email']], 'Your Moving Estimate - ' . $quote_data['quote_number'], $template);
}

function send_invoice_email($invoice_data, $quote_data, $lead_data, $type = 'new') {
    $action_text = [
        'new' => 'Your Invoice is Ready!',
        'converted' => 'Estimate Approved - Invoice Generated!'
    ];
    
    $content = '
        <h2 style="color: #007bff;">' . $action_text[$type] . '</h2>
        <p>Dear <strong>' . $lead_data['name'] . '</strong>,</p>
        <p>Great news! Your moving estimate has been approved and we have generated your invoice.</p>
        
        <div class="success">
            <h3>Invoice Details</h3>
            <p><strong>Invoice Number:</strong> ' . $invoice_data['invoice_number'] . '</p>
            <p><strong>Amount:</strong> Rs ' . number_format($invoice_data['total_amount'], 2) . '</p>
            <p><strong>Due Date:</strong> ' . date('M d, Y', strtotime($invoice_data['due_date'])) . '</p>
            <p><strong>Status:</strong> <span style="color: #ffc107;">Payment Pending</span></p>
        </div>
        
        <div class="highlight">
            <h3>Moving Schedule</h3>
            <p><strong>From:</strong> ' . $lead_data['move_from'] . '</p>
            <p><strong>To:</strong> ' . $lead_data['move_to'] . '</p>
            <p><strong>Scheduled Date:</strong> ' . date('M d, Y', strtotime($lead_data['move_date'])) . '</p>
            <p><strong>Property Size:</strong> ' . $lead_data['home_size'] . '</p>
        </div>
        
        <div class="warning">
            <h3>Payment Information</h3>
            <p><strong>Payment Options:</strong></p>
            <ul>
                <li>Cash payment on moving day</li>
                <li>Bank transfer (details will be provided)</li>
                <li>Online payment (link will be shared)</li>
            </ul>
            <p><strong>Advance Payment:</strong> 25% advance recommended to confirm booking</p>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . get_setting('website_url', '#') . '/invoice?token=' . $invoice_data['secure_token'] . '" class="btn">View Invoice</a>
        </div>
        
        <p><strong>What happens next:</strong></p>
        <ul>
            <li>Make the advance payment to confirm your booking</li>
            <li>Our team will contact you 2 days before the move</li>
            <li>Pre-move survey will be conducted if required</li>
            <li>Professional packing and moving on scheduled date</li>
        </ul>
        
        <p>Thank you for choosing us for your relocation needs!</p>
    ';
    
    $footer_note = 'Payment due by ' . date('M d, Y', strtotime($invoice_data['due_date'])) . '. Late payments may affect your moving schedule.';
    $template = get_email_template('Invoice Ready - ' . $invoice_data['invoice_number'], $content, $footer_note);
    return send_email([$lead_data['email']], 'Invoice Ready - ' . $invoice_data['invoice_number'], $template);
}

function send_contact_thank_you($contact_data) {
    $content = '
        <h2 style="color: #28a745;">Thank You for Contacting Us!</h2>
        <p>Dear <strong>' . $contact_data['name'] . '</strong>,</p>
        <p>Thank you for reaching out to us. We have received your message and our team will get back to you within 24 hours.</p>
        
        <div class="highlight">
            <h3>Your Message Details</h3>
            <p><strong>Name:</strong> ' . $contact_data['name'] . '</p>
            <p><strong>Email:</strong> ' . $contact_data['email'] . '</p>
            <p><strong>Phone:</strong> ' . $contact_data['phone'] . '</p>
            <p><strong>Subject:</strong> ' . $contact_data['subject'] . '</p>
            <p><strong>Message:</strong></p>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
                ' . nl2br($contact_data['message']) . '
            </div>
        </div>
        
        <p>In the meantime, feel free to:</p>
        <ul>
            <li>Browse our services on our website</li>
            <li>Check our social media for moving tips</li>
            <li>Call us directly for urgent inquiries</li>
        </ul>
        
        <p>We appreciate your interest in our services and look forward to helping you with your moving needs!</p>
    ';
    
    $template = get_email_template('Thank You for Your Inquiry', $content);
    return send_email([$contact_data['email']], 'Thank You for Your Inquiry', $template);
}

function send_support_notification($contact_data) {
    $content = '
        <h2 style="color: #dc3545;">New Contact Form Submission</h2>
        <div class="warning">
            <h3>Customer Details</h3>
            <p><strong>Name:</strong> ' . $contact_data['name'] . '</p>
            <p><strong>Email:</strong> ' . $contact_data['email'] . '</p>
            <p><strong>Phone:</strong> ' . $contact_data['phone'] . '</p>
            <p><strong>Subject:</strong> ' . $contact_data['subject'] . '</p>
        </div>
        
        <div class="highlight">
            <h3>Message</h3>
            <div style="background: white; padding: 15px; border-radius: 5px;">
                ' . nl2br($contact_data['message']) . '
            </div>
        </div>
        
        <p><strong>Action Required:</strong> Please respond to the customer within 24 hours.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="mailto:' . $contact_data['email'] . '?subject=Re: ' . $contact_data['subject'] . '" class="btn">Reply to Customer</a>
        </div>
    ';
    
    $template = get_email_template('New Contact Form Submission', $content);
    return send_email(get_support_emails(), 'New Contact Form Submission - ' . $contact_data['subject'], $template);
}


?>