<?php
require_once 'config.php';
$page_title = 'Cookie Policy';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="mb-4">Cookie Policy</h1>
            <p class="text-muted">Last updated: <?php echo date('F d, Y'); ?></p>
            
            <div class="card shadow">
                <div class="card-body">
                    <h2>What Are Cookies</h2>
                    <p>Cookies are small text files that are placed on your device when you visit our website. They help us provide you with a better experience by remembering your preferences and analyzing how you use our site.</p>
                    
                    <h2>How We Use Cookies</h2>
                    <p>We use cookies for the following purposes:</p>
                    
                    <h3>Essential Cookies</h3>
                    <p>These cookies are necessary for the website to function properly:</p>
                    <ul>
                        <li>Session management and user authentication</li>
                        <li>Security and fraud prevention</li>
                        <li>Load balancing and website functionality</li>
                    </ul>
                    
                    <h3>Analytics Cookies</h3>
                    <p>We use Google Analytics to understand how visitors use our website:</p>
                    <ul>
                        <li>Track page views and user interactions</li>
                        <li>Measure website performance</li>
                        <li>Identify popular content and pages</li>
                        <li>Understand user demographics and interests</li>
                    </ul>
                    
                    <h3>Advertising Cookies</h3>
                    <p>We use Google Ads and other advertising services:</p>
                    <ul>
                        <li>Show relevant advertisements</li>
                        <li>Measure ad effectiveness</li>
                        <li>Create remarketing audiences</li>
                        <li>Prevent showing the same ad repeatedly</li>
                    </ul>
                    
                    <h3>Social Media Cookies</h3>
                    <p>Social media platforms may set cookies when you interact with their content:</p>
                    <ul>
                        <li>Enable social sharing features</li>
                        <li>Track social media interactions</li>
                        <li>Personalize social media content</li>
                    </ul>
                    
                    <h2>Third-Party Cookies</h2>
                    <p>We work with third-party service providers who may set cookies:</p>
                    <ul>
                        <li><strong>Google Analytics:</strong> Website analytics and reporting</li>
                        <li><strong>Google Ads:</strong> Advertising and remarketing</li>
                        <li><strong>Facebook Pixel:</strong> Social media advertising</li>
                        <li><strong>Other advertising networks:</strong> Display advertising</li>
                    </ul>
                    
                    <h2>Managing Cookies</h2>
                    <p>You can control cookies through your browser settings:</p>
                    
                    <h3>Browser Settings</h3>
                    <ul>
                        <li>Block all cookies</li>
                        <li>Block third-party cookies only</li>
                        <li>Delete existing cookies</li>
                        <li>Set preferences for specific websites</li>
                    </ul>
                    
                    <h3>Opt-Out Options</h3>
                    <ul>
                        <li><a href="https://tools.google.com/dlpage/gaoptout" target="_blank">Google Analytics Opt-out</a></li>
                        <li><a href="https://adssettings.google.com" target="_blank">Google Ads Settings</a></li>
                        <li><a href="https://optout.networkadvertising.org" target="_blank">Network Advertising Initiative</a></li>
                        <li><a href="https://optout.aboutads.info" target="_blank">Digital Advertising Alliance</a></li>
                    </ul>
                    
                    <h2>Impact of Disabling Cookies</h2>
                    <p>Disabling cookies may affect your experience:</p>
                    <ul>
                        <li>Some website features may not work properly</li>
                        <li>You may need to re-enter information</li>
                        <li>Personalized content may not be available</li>
                        <li>Analytics data may be incomplete</li>
                    </ul>
                    
                    <h2>Cookie Retention</h2>
                    <p>Different cookies have different retention periods:</p>
                    <ul>
                        <li><strong>Session cookies:</strong> Deleted when you close your browser</li>
                        <li><strong>Persistent cookies:</strong> Remain until expiration or manual deletion</li>
                        <li><strong>Analytics cookies:</strong> Typically expire after 2 years</li>
                        <li><strong>Advertising cookies:</strong> Usually expire after 30-90 days</li>
                    </ul>
                    
                    <h2>Updates to This Policy</h2>
                    <p>We may update this cookie policy to reflect changes in our practices or applicable laws. Please check this page regularly for updates.</p>
                    
                    <h2>Contact Us</h2>
                    <p>If you have questions about our use of cookies:</p>
                    <ul>
                        <li>Email: <?php echo get_setting('company_email', 'support@packersanmovers.com'); ?></li>
                        <li>Phone: <?php echo get_setting('company_phone1', '+91 7710020974'); ?></li>
                        <li>Address: <?php echo get_setting('company_address', 'Mumbai, Maharashtra, India'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>