<?php
require_once 'config.php';
$page_title = 'Terms of Service';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="mb-4">Terms of Service</h1>
            <p class="text-muted">Last updated: <?php echo date('F d, Y'); ?></p>
            
            <div class="card shadow">
                <div class="card-body">
                    <h2>1. Acceptance of Terms</h2>
                    <p>By accessing and using PackersAnMovers services, you accept and agree to be bound by the terms and provision of this agreement.</p>
                    
                    <h2>2. Services Description</h2>
                    <p>PackersAnMovers provides professional moving and packing services including:</p>
                    <ul>
                        <li>Residential and commercial moving</li>
                        <li>Packing and unpacking services</li>
                        <li>Storage solutions</li>
                        <li>Specialty item moving</li>
                        <li>Related moving services</li>
                    </ul>
                    
                    <h2>3. Booking and Quotes</h2>
                    <p>All quotes are estimates based on the information provided. Final charges may vary based on actual services required. Quotes are valid for 30 days unless otherwise specified.</p>
                    
                    <h2>4. Payment Terms</h2>
                    <ul>
                        <li>Payment is due as specified in your service agreement</li>
                        <li>We accept cash, check, and major credit cards</li>
                        <li>Additional charges may apply for services not included in the original quote</li>
                        <li>Late payment fees may apply to overdue accounts</li>
                    </ul>
                    
                    <h2>5. Liability and Insurance</h2>
                    <p>We maintain appropriate insurance coverage for our services. Our liability is limited as follows:</p>
                    <ul>
                        <li>Basic coverage is included in all moves</li>
                        <li>Full value protection is available for additional cost</li>
                        <li>Claims must be reported within 9 months of delivery</li>
                        <li>We are not liable for items packed by the customer</li>
                    </ul>
                    
                    <h2>6. Customer Responsibilities</h2>
                    <p>Customers are responsible for:</p>
                    <ul>
                        <li>Providing accurate inventory and access information</li>
                        <li>Preparing items for moving as instructed</li>
                        <li>Being present during pickup and delivery</li>
                        <li>Providing safe access to properties</li>
                        <li>Disclosing hazardous materials</li>
                    </ul>
                    
                    <h2>7. Prohibited Items</h2>
                    <p>We cannot transport:</p>
                    <ul>
                        <li>Hazardous materials</li>
                        <li>Perishable items</li>
                        <li>Live plants (long-distance moves)</li>
                        <li>Personal documents and valuables</li>
                        <li>Illegal items</li>
                    </ul>
                    
                    <h2>8. Cancellation Policy</h2>
                    <ul>
                        <li>Cancellations must be made at least 48 hours before scheduled service</li>
                        <li>Cancellation fees may apply</li>
                        <li>Weather-related cancellations will be rescheduled without penalty</li>
                    </ul>
                    
                    <h2>9. Force Majeure</h2>
                    <p>We are not liable for delays or failures due to circumstances beyond our control, including natural disasters, strikes, or government actions.</p>
                    
                    <h2>10. Dispute Resolution</h2>
                    <p>Any disputes will be resolved through binding arbitration in accordance with Indian law. The jurisdiction for any legal proceedings shall be Mumbai, Maharashtra.</p>
                    
                    <h2>11. Privacy</h2>
                    <p>Your privacy is important to us. Please review our Privacy Policy to understand how we collect and use your information.</p>
                    
                    <h2>12. Advertising and Marketing</h2>
                    <p>Our website may display advertisements from third parties. We are not responsible for the content or accuracy of these advertisements. By using our website, you acknowledge that:</p>
                    <ul>
                        <li>We may collect data for advertising purposes</li>
                        <li>Third-party advertisers may use cookies and tracking technologies</li>
                        <li>You can opt out of personalized advertising</li>
                        <li>We are not liable for third-party advertising content</li>
                    </ul>
                    
                    <h2>13. Modifications</h2>
                    <p>We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting on our website.</p>
                    
                    <h2>13. Contact Information</h2>
                    <p>For questions about these terms, contact us:</p>
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