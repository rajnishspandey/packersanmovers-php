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
                    
                    <h2 id="refund-policy">8. Cancellation and Refund Policy</h2>
                    
                    <h3>8.1 Service Cancellation</h3>
                    <ul>
                        <li><strong>More than 72 hours before service:</strong> Full refund of advance payment minus 5% processing fee</li>
                        <li><strong>48-72 hours before service:</strong> 80% refund of advance payment</li>
                        <li><strong>24-48 hours before service:</strong> 50% refund of advance payment</li>
                        <li><strong>Less than 24 hours before service:</strong> No refund (may be rescheduled once)</li>
                        <li><strong>Weather-related cancellations:</strong> Full refund or free rescheduling</li>
                        <li><strong>Company-initiated cancellation:</strong> 100% refund plus 10% compensation</li>
                    </ul>
                    
                    <h3>8.2 Refund Process</h3>
                    <ul>
                        <li>Refund requests must be submitted in writing via email or through our website</li>
                        <li>Processing time: 7-14 business days from approval date</li>
                        <li>Refunds will be processed to the original payment method</li>
                        <li>Bank transfer refunds may take 3-5 additional business days</li>
                        <li>Processing fees and third-party charges are non-refundable</li>
                    </ul>
                    
                    <h3>8.3 Service-Related Refunds</h3>
                    <ul>
                        <li><strong>Damage to goods:</strong> Compensation as per insurance coverage terms</li>
                        <li><strong>Delayed delivery:</strong> 5% refund per day of delay (max 25%)</li>
                        <li><strong>Incomplete service:</strong> Prorated refund for undelivered services</li>
                        <li><strong>Unsatisfactory service:</strong> Case-by-case review and potential partial refund</li>
                    </ul>
                    
                    <h3>8.4 Non-Refundable Services</h3>
                    <ul>
                        <li>Survey and assessment fees (if service is not booked)</li>
                        <li>Storage charges (if items are abandoned after 30 days)</li>
                        <li>Additional services requested during the move</li>
                        <li>Third-party services arranged on customer's behalf</li>
                    </ul>
                    
                    <h3>8.5 Dispute Resolution</h3>
                    <ul>
                        <li>Refund disputes will be addressed within 48 hours of request</li>
                        <li>Independent mediation available for unresolved disputes</li>
                        <li>Consumer forum complaints will be handled as per Indian law</li>
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
                    
                    <h2>14. Contact Information</h2>
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
