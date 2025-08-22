<?php
require_once 'config.php';
$page_title = 'Business Verification & Credentials - ' . get_setting('company_name', 'Professional Packers & Movers');
include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active">Business Verification</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold text-primary">Business Verification & Credentials</h1>
                <p class="lead text-muted">Trusted, Licensed & Insured Professional Moving Services</p>
            </div>
            
            <!-- Trust Badges -->
            <div class="row mb-5">
                <div class="col-md-3 text-center mb-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                            <h5>Fully Insured</h5>
                            <p class="text-muted small">Comprehensive coverage for your belongings</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-certificate fa-3x text-primary mb-3"></i>
                            <h5>Licensed</h5>
                            <p class="text-muted small">Government approved transport license</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-award fa-3x text-warning mb-3"></i>
                            <h5>Certified</h5>
                            <p class="text-muted small">ISO quality management certified</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-users fa-3x text-info mb-3"></i>
                            <h5>Experienced</h5>
                            <p class="text-muted small"><?= date('Y') - get_setting('establishment_year', 2015) ?>+ years in business</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- License Information -->
            <div class="card mb-4 border-0 shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-certificate me-2"></i>Official Business Licenses & Registration</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong class="text-primary">Transport License Number:</strong><br>
                                <span class="h6"><?= get_setting('transport_license', 'TL-MH-2015-0123456') ?></span>
                            </div>
                            <div class="mb-3">
                                <strong class="text-primary">GST Registration Number:</strong><br>
                                <span class="h6"><?= get_setting('gst_number', '27ABCDE1234F1Z5') ?></span>
                            </div>
                            <div class="mb-3">
                                <strong class="text-primary">PAN Number:</strong><br>
                                <span class="h6"><?= get_setting('pan_number', 'ABCDE1234F') ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong class="text-primary">Business Registration:</strong><br>
                                <span class="h6"><?= get_setting('business_registration', 'REG-MH-2015-789012') ?></span>
                            </div>
                            <div class="mb-3">
                                <strong class="text-primary">Established Year:</strong><br>
                                <span class="h6"><?= get_setting('establishment_year', '2015') ?></span>
                            </div>
                            <div class="mb-3">
                                <strong class="text-primary">Total Experience:</strong><br>
                                <span class="h6"><?= date('Y') - get_setting('establishment_year', 2015) ?>+ Years</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insurance Details -->
            <div class="card mb-4 border-0 shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Insurance Coverage</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong class="text-success">General Liability Insurance:</strong><br>
                                <span class="h6">Policy #<?= get_setting('liability_insurance', 'LI-2024-567890') ?></span><br>
                                <small class="text-muted">Coverage: ₹<?= get_setting('liability_coverage', '50,00,000') ?></small>
                            </div>
                            <div class="mb-3">
                                <strong class="text-success">Goods in Transit Insurance:</strong><br>
                                <span class="h6">Policy #<?= get_setting('transit_insurance', 'GIT-2024-123456') ?></span><br>
                                <small class="text-muted">Coverage: ₹<?= get_setting('transit_coverage', '25,00,000') ?></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong class="text-success">Professional Indemnity:</strong><br>
                                <span class="h6">Policy #<?= get_setting('indemnity_insurance', 'PI-2024-789012') ?></span><br>
                                <small class="text-muted">Coverage: ₹<?= get_setting('indemnity_coverage', '10,00,000') ?></small>
                            </div>
                            <div class="mb-3">
                                <strong class="text-success">Vehicle Insurance:</strong><br>
                                <span class="h6">Comprehensive Coverage</span><br>
                                <small class="text-muted">All vehicles fully insured</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Certifications -->
            <div class="card mb-4 border-0 shadow">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-award me-2"></i>Industry Certifications & Memberships</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-3"></i>
                                    <div>
                                        <strong>ISO 9001:2015 Certified</strong><br>
                                        <small class="text-muted">Quality Management System</small>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-3"></i>
                                    <div>
                                        <strong>AITC Member</strong><br>
                                        <small class="text-muted">All India Motor Transport Congress</small>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-3"></i>
                                    <div>
                                        <strong>IBA Certified</strong><br>
                                        <small class="text-muted">Indian Banks Association Approved</small>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-3"></i>
                                    <div>
                                        <strong>FIDR Registered</strong><br>
                                        <small class="text-muted">Freight Industry Development & Regulatory</small>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-3"></i>
                                    <div>
                                        <strong>MSME Registered</strong><br>
                                        <small class="text-muted">Micro, Small & Medium Enterprises</small>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-3"></i>
                                    <div>
                                        <strong>Digital India Initiative</strong><br>
                                        <small class="text-muted">Government of India Recognized</small>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Statistics -->
            <div class="card mb-4 border-0 shadow">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-chart-line me-2"></i>Business Performance & Trust Metrics</h4>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="border-end">
                                <h3 class="text-primary fw-bold"><?= get_setting('completed_moves', '5,000') ?>+</h3>
                                <p class="text-muted mb-0">Successful Moves</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border-end">
                                <h3 class="text-success fw-bold"><?= get_setting('customer_rating', '4.8') ?>/5</h3>
                                <p class="text-muted mb-0">Customer Rating</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border-end">
                                <h3 class="text-warning fw-bold"><?= get_setting('cities_covered', '150') ?>+</h3>
                                <p class="text-muted mb-0">Cities Covered</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h3 class="text-info fw-bold"><?= get_setting('team_size', '200') ?>+</h3>
                            <p class="text-muted mb-0">Team Members</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card border-0 shadow">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="fas fa-address-book me-2"></i>Verified Contact Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong class="text-dark">Registered Office Address:</strong><br>
                                <address class="mb-0">
                                    <?= get_setting('registered_address', 'Office No. 123, Business Center,<br>Sector 15, CBD Belapur,<br>Navi Mumbai - 400614, Maharashtra, India') ?>
                                </address>
                            </div>
                            <div class="mb-3">
                                <strong class="text-dark">Customer Support:</strong><br>
                                <i class="fas fa-phone text-primary me-2"></i><?= get_setting('customer_support_phone', '+91-9876543210') ?><br>
                                <i class="fas fa-envelope text-primary me-2"></i><?= get_setting('customer_support_email', 'support@packersanmovers.com') ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong class="text-dark">Business Hours:</strong><br>
                                Monday - Saturday: <?= get_setting('business_hours_weekday', '9:00 AM - 7:00 PM') ?><br>
                                Sunday: <?= get_setting('business_hours_sunday', '10:00 AM - 5:00 PM') ?>
                            </div>
                            <div class="mb-3">
                                <strong class="text-dark">Emergency Contact:</strong><br>
                                <i class="fas fa-phone text-danger me-2"></i><?= get_setting('emergency_contact', '+91-9876543211') ?><br>
                                <small class="text-muted">Available 24/7 for urgent assistance</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trust Guarantee -->
            <div class="text-center mt-5 p-4 bg-light rounded">
                <h4 class="text-primary mb-3">Our Commitment to You</h4>
                <p class="lead text-muted mb-4">We are committed to providing transparent, reliable, and professional moving services. All our credentials are verified and up-to-date.</p>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <i class="fas fa-handshake text-success fa-2x mb-2"></i><br>
                        <strong>100% Transparency</strong>
                    </div>
                    <div class="col-md-4 mb-2">
                        <i class="fas fa-clock text-primary fa-2x mb-2"></i><br>
                        <strong>On-Time Delivery</strong>
                    </div>
                    <div class="col-md-4 mb-2">
                        <i class="fas fa-money-bill-wave text-warning fa-2x mb-2"></i><br>
                        <strong>No Hidden Charges</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "MovingCompany",
  "name": "<?= get_setting('company_name', 'Professional Packers & Movers') ?>",
  "description": "Licensed and insured professional moving company with <?= date('Y') - get_setting('establishment_year', 2015) ?>+ years of experience",
  "url": "<?= get_setting('website_url', 'https://packersanmovers.com') ?>",
  "telephone": "<?= get_setting('customer_support_phone', '+91-9876543210') ?>",
  "email": "<?= get_setting('customer_support_email', 'support@packersanmovers.com') ?>",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "<?= get_setting('street_address', 'Office No. 123, Business Center, Sector 15') ?>",
    "addressLocality": "<?= get_setting('city', 'Navi Mumbai') ?>",
    "addressRegion": "<?= get_setting('state', 'Maharashtra') ?>",
    "postalCode": "<?= get_setting('postal_code', '400614') ?>",
    "addressCountry": "IN"
  },
  "foundingDate": "<?= get_setting('establishment_year', '2015') ?>",
  "numberOfEmployees": "<?= get_setting('team_size', '200') ?>",
  "areaServed": "India",
  "serviceType": "Residential and Commercial Moving Services",
  "hasCredential": [
    {
      "@type": "EducationalOccupationalCredential",
      "credentialCategory": "license",
      "recognizedBy": {
        "@type": "Organization",
        "name": "Transport Department, Government of India"
      }
    },
    {
      "@type": "EducationalOccupationalCredential",
      "credentialCategory": "certification",
      "recognizedBy": {
        "@type": "Organization",
        "name": "ISO 9001:2015"
      }
    }
  ],
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?= get_setting('customer_rating', '4.8') ?>",
    "bestRating": "5",
    "worstRating": "1",
    "ratingCount": "<?= get_setting('total_reviews', '1250') ?>"
  }
}
</script>

<?php include 'includes/footer.php'; ?>
