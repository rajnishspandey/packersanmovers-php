<?php
require_once 'config.php';
require_admin();

$page_title = 'System Settings - ' . get_setting('company_name', 'Professional Packers & Movers');

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    require_csrf();
    
    $settings_to_update = [
        // Company Information
        'company_name', 'company_tagline', 'company_email', 'company_phone1', 'company_phone2',
        'company_address', 'company_website', 'registered_address', 'establishment_year',
        
        // Business Credentials
        'transport_license', 'gst_number', 'pan_number', 'business_registration',
        'liability_insurance', 'transit_insurance', 'indemnity_insurance',
        'liability_coverage', 'transit_coverage', 'indemnity_coverage',
        
        // Business Metrics
        'completed_moves', 'customer_rating', 'cities_covered', 'team_size',
        'total_reviews', 'ontime_percentage', 'insurance_coverage',
        
        // Pricing Configuration
        'price_1bhk', 'price_2bhk', 'price_3bhk', 'price_4bhk', 'price_villa', 'price_office',
        'price_packing', 'price_unpacking', 'price_storage', 'price_insurance', 'price_express',
        'rate_local', 'rate_intercity', 'rate_longdistance',
        'charge_stairs', 'charge_longcarry', 'charge_waiting', 'charge_weekend', 
        'charge_fragile', 'charge_appliance', 'advance_percentage',
        
        // Contact Information
        'customer_support_phone', 'customer_support_email', 'emergency_contact',
        'business_hours_weekday', 'business_hours_sunday',
        
        // SEO & Marketing
        'meta_description', 'meta_keywords', 'hero_subtitle',
        
        // Social Media
        'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url',
        
        // Email Configuration
        'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_from',
        
        // Website Configuration
        'website_name', 'website_url', 'primary_color'
    ];
    
    try {
        $pdo->beginTransaction();
        
        foreach ($settings_to_update as $key) {
            if (isset($_POST[$key])) {
                $value = sanitize_input($_POST[$key]);
                update_setting($key, $value);
            }
        }
        
        $pdo->commit();
        $_SESSION['message'] = 'Settings updated successfully!';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Failed to update settings: ' . $e->getMessage();
    }
    
    redirect('/admin-settings');
}

include 'includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-cogs"></i> System Settings</h1>
        <a href="/admin" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <form method="POST" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_settings">
        
        <!-- Company Information Tab -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-building me-2"></i>Company Information</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Company Name *</label>
                        <input type="text" name="company_name" class="form-control" 
                               value="<?= get_setting('company_name', 'Professional Packers & Movers') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Company Tagline</label>
                        <input type="text" name="company_tagline" class="form-control" 
                               value="<?= get_setting('company_tagline', 'Trusted Moving Services Across India') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Primary Email *</label>
                        <input type="email" name="company_email" class="form-control" 
                               value="<?= get_setting('company_email', 'info@packersanmovers.com') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Website URL</label>
                        <input type="url" name="company_website" class="form-control" 
                               value="<?= get_setting('company_website', 'https://packersanmovers.com') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Phone Number 1 *</label>
                        <input type="tel" name="company_phone1" class="form-control" 
                               value="<?= get_setting('company_phone1', '+91-9876543210') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Phone Number 2</label>
                        <input type="tel" name="company_phone2" class="form-control" 
                               value="<?= get_setting('company_phone2', '+91-9876543211') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Establishment Year</label>
                        <input type="number" name="establishment_year" class="form-control" min="1990" max="<?= date('Y') ?>"
                               value="<?= get_setting('establishment_year', '2015') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Primary Color</label>
                        <input type="color" name="primary_color" class="form-control form-control-color" 
                               value="<?= get_setting('primary_color', '#007bff') ?>">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Business Address</label>
                        <textarea name="company_address" class="form-control" rows="3"><?= get_setting('company_address', 'Office No. 123, Business Center, Sector 15, CBD Belapur, Navi Mumbai - 400614, Maharashtra, India') ?></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Registered Office Address</label>
                        <textarea name="registered_address" class="form-control" rows="3"><?= get_setting('registered_address', 'Office No. 123, Business Center, Sector 15, CBD Belapur, Navi Mumbai - 400614, Maharashtra, India') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Credentials -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0"><i class="fas fa-certificate me-2"></i>Business Credentials & Licenses</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Transport License Number</label>
                        <input type="text" name="transport_license" class="form-control" 
                               value="<?= get_setting('transport_license', 'TL-MH-2015-0123456') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">GST Registration Number</label>
                        <input type="text" name="gst_number" class="form-control" 
                               value="<?= get_setting('gst_number', '27ABCDE1234F1Z5') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">PAN Number</label>
                        <input type="text" name="pan_number" class="form-control" 
                               value="<?= get_setting('pan_number', 'ABCDE1234F') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Business Registration Number</label>
                        <input type="text" name="business_registration" class="form-control" 
                               value="<?= get_setting('business_registration', 'REG-MH-2015-789012') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Liability Insurance Policy</label>
                        <input type="text" name="liability_insurance" class="form-control" 
                               value="<?= get_setting('liability_insurance', 'LI-2024-567890') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Transit Insurance Policy</label>
                        <input type="text" name="transit_insurance" class="form-control" 
                               value="<?= get_setting('transit_insurance', 'GIT-2024-123456') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Indemnity Insurance Policy</label>
                        <input type="text" name="indemnity_insurance" class="form-control" 
                               value="<?= get_setting('indemnity_insurance', 'PI-2024-789012') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Liability Coverage (₹)</label>
                        <input type="text" name="liability_coverage" class="form-control" 
                               value="<?= get_setting('liability_coverage', '50,00,000') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Transit Coverage (₹)</label>
                        <input type="text" name="transit_coverage" class="form-control" 
                               value="<?= get_setting('transit_coverage', '25,00,000') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Indemnity Coverage (₹)</label>
                        <input type="text" name="indemnity_coverage" class="form-control" 
                               value="<?= get_setting('indemnity_coverage', '10,00,000') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Metrics -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="fas fa-chart-line me-2"></i>Business Performance Metrics</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Completed Moves</label>
                        <input type="text" name="completed_moves" class="form-control" 
                               value="<?= get_setting('completed_moves', '5,000') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Customer Rating (out of 5)</label>
                        <input type="number" name="customer_rating" class="form-control" step="0.1" min="1" max="5"
                               value="<?= get_setting('customer_rating', '4.8') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Cities Covered</label>
                        <input type="number" name="cities_covered" class="form-control" 
                               value="<?= get_setting('cities_covered', '150') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Team Size</label>
                        <input type="number" name="team_size" class="form-control" 
                               value="<?= get_setting('team_size', '200') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Total Reviews</label>
                        <input type="number" name="total_reviews" class="form-control" 
                               value="<?= get_setting('total_reviews', '1250') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">On-Time Delivery (%)</label>
                        <input type="number" name="ontime_percentage" class="form-control" min="0" max="100"
                               value="<?= get_setting('ontime_percentage', '98') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Insurance Coverage</label>
                        <input type="text" name="insurance_coverage" class="form-control" 
                               value="<?= get_setting('insurance_coverage', '25,00,000') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Configuration -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0"><i class="fas fa-rupee-sign me-2"></i>Pricing Configuration</h4>
            </div>
            <div class="card-body">
                <h5 class="text-primary mb-3">Base Pricing (₹)</h5>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">1 BHK</label>
                        <input type="number" name="price_1bhk" class="form-control" 
                               value="<?= get_setting('price_1bhk', '8000') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">2 BHK</label>
                        <input type="number" name="price_2bhk" class="form-control" 
                               value="<?= get_setting('price_2bhk', '12000') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">3 BHK</label>
                        <input type="number" name="price_3bhk" class="form-control" 
                               value="<?= get_setting('price_3bhk', '18000') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">4 BHK</label>
                        <input type="number" name="price_4bhk" class="form-control" 
                               value="<?= get_setting('price_4bhk', '25000') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Villa/Bungalow</label>
                        <input type="number" name="price_villa" class="form-control" 
                               value="<?= get_setting('price_villa', '35000') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Office</label>
                        <input type="number" name="price_office" class="form-control" 
                               value="<?= get_setting('price_office', '15000') ?>">
                    </div>
                </div>
                
                <h5 class="text-success mb-3 mt-4">Additional Services (₹)</h5>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Packing</label>
                        <input type="number" name="price_packing" class="form-control" 
                               value="<?= get_setting('price_packing', '2000') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Unpacking</label>
                        <input type="number" name="price_unpacking" class="form-control" 
                               value="<?= get_setting('price_unpacking', '1500') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Storage (per day)</label>
                        <input type="number" name="price_storage" class="form-control" 
                               value="<?= get_setting('price_storage', '1000') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Insurance</label>
                        <input type="number" name="price_insurance" class="form-control" 
                               value="<?= get_setting('price_insurance', '500') ?>">
                    </div>
                </div>
                
                <h5 class="text-danger mb-3 mt-4">Distance Rates (₹ per KM)</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Local (0-50 KM)</label>
                        <input type="number" name="rate_local" class="form-control" 
                               value="<?= get_setting('rate_local', '15') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Intercity (50-500 KM)</label>
                        <input type="number" name="rate_intercity" class="form-control" 
                               value="<?= get_setting('rate_intercity', '25') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Long Distance (500+ KM)</label>
                        <input type="number" name="rate_longdistance" class="form-control" 
                               value="<?= get_setting('rate_longdistance', '35') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h4 class="mb-0"><i class="fas fa-phone me-2"></i>Contact Information</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Customer Support Phone</label>
                        <input type="tel" name="customer_support_phone" class="form-control" 
                               value="<?= get_setting('customer_support_phone', '+91-9876543210') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Customer Support Email</label>
                        <input type="email" name="customer_support_email" class="form-control" 
                               value="<?= get_setting('customer_support_email', 'support@packersanmovers.com') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Emergency Contact</label>
                        <input type="tel" name="emergency_contact" class="form-control" 
                               value="<?= get_setting('emergency_contact', '+91-9876543211') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Weekday Business Hours</label>
                        <input type="text" name="business_hours_weekday" class="form-control" 
                               value="<?= get_setting('business_hours_weekday', '9:00 AM - 7:00 PM') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Sunday Business Hours</label>
                        <input type="text" name="business_hours_sunday" class="form-control" 
                               value="<?= get_setting('business_hours_sunday', '10:00 AM - 5:00 PM') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEO & Marketing -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0"><i class="fas fa-search me-2"></i>SEO & Marketing</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Hero Subtitle</label>
                        <input type="text" name="hero_subtitle" class="form-control" 
                               value="<?= get_setting('hero_subtitle', 'Trusted, Reliable & Affordable Moving Services Across India') ?>">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="3" maxlength="160"><?= get_setting('meta_description', 'Professional packers and movers with ' . (date('Y') - get_setting('establishment_year', 2015)) . '+ years experience. Licensed, insured, and trusted by 5000+ customers. Get instant quote for home and office relocation.') ?></textarea>
                        <small class="text-muted">Recommended: 150-160 characters</small>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Meta Keywords</label>
                        <textarea name="meta_keywords" class="form-control" rows="2"><?= get_setting('meta_keywords', 'packers and movers, home shifting, office relocation, professional movers, licensed packers, insured moving services, household shifting, commercial moving') ?></textarea>
                        <small class="text-muted">Separate keywords with commas</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media -->
        <div class="card mb-4">
            <div class="card-header" style="background: linear-gradient(45deg, #3b5998, #1da1f2, #e4405f, #0077b5); color: white;">
                <h4 class="mb-0"><i class="fas fa-share-alt me-2"></i>Social Media Links</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold"><i class="fab fa-facebook text-primary me-2"></i>Facebook URL</label>
                        <input type="url" name="facebook_url" class="form-control" 
                               value="<?= get_setting('facebook_url', '') ?>" placeholder="https://facebook.com/yourpage">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold"><i class="fab fa-twitter text-info me-2"></i>Twitter URL</label>
                        <input type="url" name="twitter_url" class="form-control" 
                               value="<?= get_setting('twitter_url', '') ?>" placeholder="https://twitter.com/yourhandle">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold"><i class="fab fa-instagram text-danger me-2"></i>Instagram URL</label>
                        <input type="url" name="instagram_url" class="form-control" 
                               value="<?= get_setting('instagram_url', '') ?>" placeholder="https://instagram.com/yourprofile">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold"><i class="fab fa-linkedin text-primary me-2"></i>LinkedIn URL</label>
                        <input type="url" name="linkedin_url" class="form-control" 
                               value="<?= get_setting('linkedin_url', '') ?>" placeholder="https://linkedin.com/company/yourcompany">
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Configuration -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Email Configuration</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Email configuration is required for sending notifications and quotes to customers.
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">SMTP Host</label>
                        <input type="text" name="mail_host" class="form-control" 
                               value="<?= get_setting('mail_host', '') ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">SMTP Port</label>
                        <input type="number" name="mail_port" class="form-control" 
                               value="<?= get_setting('mail_port', '587') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">SMTP Username</label>
                        <input type="email" name="mail_username" class="form-control" 
                               value="<?= get_setting('mail_username', '') ?>" placeholder="your-email@gmail.com">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">SMTP Password</label>
                        <input type="password" name="mail_password" class="form-control" 
                               value="<?= get_setting('mail_password', '') ?>" placeholder="Your app password">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">From Email</label>
                        <input type="email" name="mail_from" class="form-control" 
                               value="<?= get_setting('mail_from', '') ?>" placeholder="noreply@yourcompany.com">
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-save me-2"></i>Save All Settings
            </button>
        </div>
    </form>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Auto-save draft functionality
let saveTimeout;
function autoSave() {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        // Could implement auto-save to localStorage here
        console.log('Auto-saving draft...');
    }, 2000);
}

// Add auto-save to all inputs
document.querySelectorAll('input, textarea, select').forEach(element => {
    element.addEventListener('input', autoSave);
});
</script>

<?php include 'includes/footer.php'; ?>
