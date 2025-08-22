<?php
require_once 'config.php';
$page_title = 'Privacy Policy';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="mb-4">Privacy Policy</h1>
            <p class="text-muted">Last updated: <?php echo date('F d, Y'); ?></p>
            
            <div class="card shadow">
                <div class="card-body">
                    <h2>1. Information We Collect</h2>
                    <p>We collect information you provide directly to us, such as when you:</p>
                    <ul>
                        <li>Request a moving quote</li>
                        <li>Contact us through our website</li>
                        <li>Subscribe to our newsletter</li>
                        <li>Use our services</li>
                    </ul>
                    
                    <h3>Personal Information</h3>
                    <p>This may include:</p>
                    <ul>
                        <li>Name and contact information (email, phone, address)</li>
                        <li>Moving details (origin, destination, dates)</li>
                        <li>Property information</li>
                        <li>Payment information</li>
                    </ul>
                    
                    <h2>2. How We Use Your Information</h2>
                    <p>We use the information we collect to:</p>
                    <ul>
                        <li>Provide moving services and quotes</li>
                        <li>Communicate with you about our services</li>
                        <li>Process payments</li>
                        <li>Improve our services</li>
                        <li>Send marketing communications (with your consent)</li>
                        <li>Comply with legal obligations</li>
                    </ul>
                    
                    <h2>3. Information Sharing</h2>
                    <p>We do not sell, trade, or rent your personal information to third parties. We may share your information in the following circumstances:</p>
                    <ul>
                        <li>With service providers who assist us in our operations</li>
                        <li>When required by law or to protect our rights</li>
                        <li>In connection with a business transfer</li>
                        <li>With your consent</li>
                    </ul>
                    
                    <h2>4. Data Security</h2>
                    <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the internet is 100% secure.</p>
                    
                    <h2>5. Cookies and Tracking Technologies</h2>
                    <p>We use cookies, web beacons, and similar tracking technologies to:</p>
                    <ul>
                        <li>Remember your preferences and settings</li>
                        <li>Analyze website traffic and user behavior</li>
                        <li>Improve user experience and website functionality</li>
                        <li>Provide personalized content and targeted advertising</li>
                        <li>Measure advertising effectiveness</li>
                        <li>Enable social media features</li>
                    </ul>
                    <p>You can control cookies through your browser settings. Note that disabling cookies may affect website functionality.</p>
                    
                    <h3>Google Services</h3>
                    <p>We use Google services including Google Analytics and Google Ads which may collect and process data. Please review Google's Privacy Policy at https://policies.google.com/privacy for more information.</p>
                    
                    <h2>6. Third-Party Services and Advertising</h2>
                    <p>We work with third-party service providers and advertising partners who may collect information about your use of our website. This includes:</p>
                    <ul>
                        <li>Google Analytics for website analytics</li>
                        <li>Google Ads for advertising services</li>
                        <li>Social media platforms for sharing features</li>
                        <li>Payment processors for transaction processing</li>
                    </ul>
                    <p>These third parties have their own privacy policies and we are not responsible for their privacy practices. We encourage you to review their policies.</p>
                    
                    <h3>Advertising and Remarketing</h3>
                    <p>We may use remarketing and advertising services to show you relevant ads on other websites. You can opt out of personalized advertising by visiting Google's Ad Settings or the Network Advertising Initiative opt-out page.</p>
                    
                    <h2>7. Data Retention</h2>
                    <p>We retain your personal information for as long as necessary to provide our services and comply with legal obligations.</p>
                    
                    <h2>8. Your Rights</h2>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access your personal information</li>
                        <li>Correct inaccurate information</li>
                        <li>Request deletion of your information</li>
                        <li>Opt-out of marketing communications</li>
                        <li>File a complaint with regulatory authorities</li>
                    </ul>
                    
                    <h2>9. Children's Privacy</h2>
                    <p>Our services are not intended for children under 13. We do not knowingly collect personal information from children under 13.</p>
                    
                    <h2>10. Changes to This Policy</h2>
                    <p>We may update this privacy policy from time to time. We will notify you of any changes by posting the new policy on this page.</p>
                    
                    <h2>11. Google Ads and Analytics</h2>
                    <p>We use Google Ads and Google Analytics to improve our services and show relevant advertisements. These services may:</p>
                    <ul>
                        <li>Collect information about your visits to our website</li>
                        <li>Use cookies to serve ads based on your interests</li>
                        <li>Track conversions and measure ad performance</li>
                        <li>Create audience segments for remarketing</li>
                    </ul>
                    <p>You can opt out of Google's use of cookies by visiting Google's Ads Settings or by downloading the Google Analytics opt-out browser add-on.</p>
                    
                    <h2>12. Contact Us</h2>
                    <p>If you have any questions about this privacy policy, please contact us:</p>
                    <ul>
                        <li>Email: <?php echo get_setting('company_email', 'Email not configured'); ?></li>
                        <li>Phone: <?php echo get_setting('company_phone1', 'Phone not configured'); ?></li>
                        <li>Address: <?php echo get_setting('company_address', 'Address not configured'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>