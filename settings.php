<?php
require_once 'config.php';
require_admin();

$page_title = 'Settings';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $settings = [
        'company_name',
        'company_address', 
        'company_phone1',
        'company_phone2',
        'company_email',
        'company_website',
        'company_tagline',
        'gst_number',

        'facebook_url',
        'twitter_url',
        'instagram_url',
        'linkedin_url',
        'website_name',
        'website_url',
        'support_emails',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_from',

    ];
    
    foreach ($settings as $setting) {
        if (isset($_POST[$setting])) {
            update_setting($setting, sanitize_input($_POST[$setting]));
        }
    }
    
    $_SESSION['message'] = 'Settings updated successfully!';
    redirect('/settings');
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="mb-4">Website Settings</h1>
            
            <div class="card shadow">
                <div class="card-body">
                    <form method="POST">
                        <h3>Company Information</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo get_setting('company_name'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_email" class="form-label">Company Email</label>
                                <input type="email" class="form-control" id="company_email" name="company_email" value="<?php echo get_setting('company_email'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="company_address" class="form-label">Company Address</label>
                            <input type="text" class="form-control" id="company_address" name="company_address" value="<?php echo get_setting('company_address'); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="company_phone1" class="form-label">Primary Phone</label>
                                <input type="text" class="form-control" id="company_phone1" name="company_phone1" value="<?php echo get_setting('company_phone1'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_phone2" class="form-label">Secondary Phone</label>
                                <input type="text" class="form-control" id="company_phone2" name="company_phone2" value="<?php echo get_setting('company_phone2'); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="company_website" class="form-label">Website URL</label>
                            <input type="url" class="form-control" id="company_website" name="company_website" value="<?php echo get_setting('company_website'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="company_tagline" class="form-label">Company Tagline</label>
                            <input type="text" class="form-control" id="company_tagline" name="company_tagline" value="<?php echo get_setting('company_tagline'); ?>" placeholder="An Arkyn Enterprises Unit">
                            <small class="text-muted">Small text displayed below company name (optional)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="gst_number" class="form-label">GST Number</label>
                            <input type="text" class="form-control" id="gst_number" name="gst_number" value="<?php echo get_setting('gst_number'); ?>" placeholder="22AAAAA0000A1Z5">
                            <small class="text-muted">GST number will be displayed on tax-enabled bills (optional)</small>
                        </div>
                        

                        
                        <hr>
                        <h3>Website Settings</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="website_name" class="form-label">Website Name</label>
                                <input type="text" class="form-control" id="website_name" name="website_name" value="<?php echo get_setting('website_name'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="website_url" class="form-label">Website URL</label>
                                <input type="url" class="form-control" id="website_url" name="website_url" value="<?php echo get_setting('website_url'); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="support_emails" class="form-label">Support Emails (comma separated)</label>
                            <textarea class="form-control" id="support_emails" name="support_emails" rows="2"><?php echo get_setting('support_emails'); ?></textarea>
                        </div>
                        
                        <hr>
                        <h3>Email Configuration</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mail_host" class="form-label">SMTP Host</label>
                                <input type="text" class="form-control" id="mail_host" name="mail_host" value="<?php echo get_setting('mail_host'); ?>" placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mail_port" class="form-label">SMTP Port</label>
                                <select class="form-select" id="mail_port" name="mail_port">
                                    <option value="587" <?php echo get_setting('mail_port') == '587' ? 'selected' : ''; ?>>587 (STARTTLS)</option>
                                    <option value="465" <?php echo get_setting('mail_port') == '465' ? 'selected' : ''; ?>>465 (SSL)</option>
                                    <option value="25" <?php echo get_setting('mail_port') == '25' ? 'selected' : ''; ?>>25 (No encryption)</option>
                                </select>
                                <small class="text-muted">587 (STARTTLS) or 465 (SSL) recommended for security</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mail_username" class="form-label">SMTP Username</label>
                                <input type="email" class="form-control" id="mail_username" name="mail_username" value="<?php echo get_setting('mail_username'); ?>" placeholder="your-email@domain.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mail_password" class="form-label">SMTP Password</label>
                                <input type="password" class="form-control" id="mail_password" name="mail_password" value="<?php echo get_setting('mail_password'); ?>" placeholder="App password or regular password">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mail_from" class="form-label">From Email Address</label>
                            <input type="email" class="form-control" id="mail_from" name="mail_from" value="<?php echo get_setting('mail_from'); ?>" placeholder="noreply@yourdomain.com">
                        </div>
                        
                        <div class="mb-3">
                            <a href="/test-email" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-paper-plane"></i> Test Email Configuration
                            </a>
                        </div>
                        

                        <h3>Social Media Links</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="facebook_url" class="form-label">Facebook URL</label>
                                <input type="url" class="form-control" id="facebook_url" name="facebook_url" value="<?php echo get_setting('facebook_url'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="twitter_url" class="form-label">Twitter URL</label>
                                <input type="url" class="form-control" id="twitter_url" name="twitter_url" value="<?php echo get_setting('twitter_url'); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="instagram_url" class="form-label">Instagram URL</label>
                                <input type="url" class="form-control" id="instagram_url" name="instagram_url" value="<?php echo get_setting('instagram_url'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                                <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" value="<?php echo get_setting('linkedin_url'); ?>">
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">Update Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>