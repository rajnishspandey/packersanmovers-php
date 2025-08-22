<?php
require_once 'config.php';
$page_title = 'Disclaimer';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="mb-4">Disclaimer</h1>
            <p class="text-muted">Last updated: <?php echo date('F d, Y'); ?></p>
            
            <div class="card shadow">
                <div class="card-body">
                    <h2>1. General Information</h2>
                    <p>The information on this website is provided on an "as is" basis. To the fullest extent permitted by law, PackersAnMovers excludes all representations, warranties, obligations, and liabilities arising out of or in connection with this website and its contents.</p>
                    
                    <h2>2. Service Estimates</h2>
                    <p>All quotes and estimates provided are approximate and based on the information provided by the customer. Actual costs may vary depending on:</p>
                    <ul>
                        <li>Actual volume and weight of items</li>
                        <li>Access conditions at pickup and delivery locations</li>
                        <li>Additional services required</li>
                        <li>Distance and route conditions</li>
                        <li>Seasonal demand and availability</li>
                    </ul>
                    
                    <h2>3. Service Availability</h2>
                    <p>Service availability is subject to:</p>
                    <ul>
                        <li>Geographic coverage areas</li>
                        <li>Seasonal availability</li>
                        <li>Equipment and crew availability</li>
                        <li>Weather conditions</li>
                        <li>Regulatory restrictions</li>
                    </ul>
                    
                    <h2>4. Third-Party Links</h2>
                    <p>Our website may contain links to third-party websites. We do not endorse or assume responsibility for the content, privacy policies, or practices of third-party sites.</p>
                    
                    <h2>5. Accuracy of Information</h2>
                    <p>While we strive to keep information accurate and up-to-date, we make no representations or warranties about the completeness, accuracy, reliability, or suitability of the information on this website.</p>
                    
                    <h2>6. Professional Advice</h2>
                    <p>The information on this website is for general informational purposes only and should not be considered as professional advice. For specific moving situations, please consult with our moving specialists.</p>
                    
                    <h2>7. Limitation of Liability</h2>
                    <p>PackersAnMovers shall not be liable for any direct, indirect, incidental, consequential, or punitive damages arising from:</p>
                    <ul>
                        <li>Use of this website</li>
                        <li>Inability to use this website</li>
                        <li>Reliance on information provided</li>
                        <li>Technical issues or interruptions</li>
                    </ul>
                    
                    <h2>8. Force Majeure</h2>
                    <p>We are not responsible for delays, damages, or failures in performance due to circumstances beyond our reasonable control, including:</p>
                    <ul>
                        <li>Natural disasters</li>
                        <li>Government actions or regulations</li>
                        <li>Labor strikes or disputes</li>
                        <li>Transportation delays</li>
                        <li>Equipment failures</li>
                    </ul>
                    
                    <h2>9. Insurance Coverage</h2>
                    <p>While we maintain appropriate insurance coverage, customers are advised to:</p>
                    <ul>
                        <li>Review their homeowner's or renter's insurance policies</li>
                        <li>Consider additional coverage for high-value items</li>
                        <li>Understand the difference between basic and full-value protection</li>
                        <li>Report any damages promptly</li>
                    </ul>
                    
                    <h2>10. Regulatory Compliance</h2>
                    <p>Our services are subject to applicable local, state, and federal regulations. Some restrictions may apply to certain types of moves or items.</p>
                    
                    <h2>11. Changes to Services</h2>
                    <p>We reserve the right to modify, suspend, or discontinue any aspect of our services at any time without prior notice.</p>
                    
                    <h2>12. Advertising and Third-Party Content</h2>
                    <p>Our website may contain advertisements and links to third-party websites. We disclaim all responsibility for:</p>
                    <ul>
                        <li>The accuracy of advertising content</li>
                        <li>Products or services advertised by third parties</li>
                        <li>The privacy practices of advertisers</li>
                        <li>Any transactions with third-party advertisers</li>
                        <li>The availability or functionality of advertised services</li>
                    </ul>
                    <p>Users interact with advertisements at their own risk and should verify information independently.</p>
                    
                    <h2>13. Contact Information</h2>
                    <p>If you have questions about this disclaimer, please contact us:</p>
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