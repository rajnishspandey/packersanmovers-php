<?php
require_once 'config.php';
require_once 'includes/cashfree-payments.php';

$page_title = 'SaaS Plans - PackersAnMovers Business Solutions';

// Get subscription plans
$stmt = $pdo->query("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC");
$plans = $stmt->fetchAll();

// Group plans by type
$monthly_plans = array_filter($plans, function($plan) { return $plan['plan_type'] === 'monthly'; });
$yearly_plans = array_filter($plans, function($plan) { return $plan['plan_type'] === 'yearly'; });

include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Hero Section -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold text-primary">Scale Your Packers & Movers Business</h1>
        <p class="lead text-muted">Complete business management solution for moving companies</p>
        <div class="mt-4">
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="planType" id="monthly" checked>
                <label class="btn btn-outline-primary" for="monthly">Monthly Plans</label>
                
                <input type="radio" class="btn-check" name="planType" id="yearly">
                <label class="btn btn-outline-primary" for="yearly">Yearly Plans <span class="badge bg-success">Save 20%</span></label>
            </div>
        </div>
    </div>

    <!-- Monthly Plans -->
    <div id="monthly-plans" class="pricing-section">
        <div class="row">
            <?php foreach ($monthly_plans as $plan): ?>
            <div class="col-lg-4 mb-4">
                <div class="card h-100 <?php echo $plan['is_popular'] ? 'border-primary shadow-lg' : 'border-0 shadow'; ?>">
                    <?php if ($plan['is_popular']): ?>
                    <div class="card-header bg-primary text-white text-center">
                        <small class="fw-bold">MOST POPULAR</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body text-center">
                        <h3 class="card-title text-primary"><?php echo $plan['plan_name']; ?></h3>
                        <div class="mb-3">
                            <span class="h2 fw-bold">₹<?php echo number_format($plan['price']); ?></span>
                            <small class="text-muted">/month</small>
                        </div>
                        
                        <ul class="list-unstyled text-start">
                            <?php 
                            $features = json_decode($plan['features'], true);
                            foreach ($features as $feature): 
                            ?>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo $feature; ?>
                            </li>
                            <?php endforeach; ?>
                            
                            <li class="mb-2">
                                <i class="fas fa-users text-primary me-2"></i>
                                Up to <?php echo $plan['max_leads']; ?> leads/month
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-file-alt text-primary me-2"></i>
                                <?php echo $plan['max_quotes']; ?> quotes/month
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-receipt text-primary me-2"></i>
                                <?php echo $plan['max_invoices']; ?> invoices/month
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-user-tie text-primary me-2"></i>
                                <?php echo $plan['max_users']; ?> team members
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-cloud text-primary me-2"></i>
                                <?php echo $plan['storage_limit_gb']; ?>GB storage
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-footer bg-transparent">
                        <button class="btn <?php echo $plan['is_popular'] ? 'btn-primary' : 'btn-outline-primary'; ?> w-100" 
                                onclick="subscribeToPlan(<?php echo $plan['id']; ?>, '<?php echo $plan['plan_name']; ?>', <?php echo $plan['price']; ?>)">
                            Start 14-Day Free Trial
                        </button>
                        <small class="text-muted d-block text-center mt-2">No credit card required</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Yearly Plans -->
    <div id="yearly-plans" class="pricing-section" style="display: none;">
        <div class="row">
            <?php foreach ($yearly_plans as $plan): ?>
            <div class="col-lg-4 mb-4">
                <div class="card h-100 <?php echo $plan['is_popular'] ? 'border-primary shadow-lg' : 'border-0 shadow'; ?>">
                    <?php if ($plan['is_popular']): ?>
                    <div class="card-header bg-primary text-white text-center">
                        <small class="fw-bold">MOST POPULAR</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body text-center">
                        <h3 class="card-title text-primary"><?php echo $plan['plan_name']; ?></h3>
                        <div class="mb-3">
                            <span class="h2 fw-bold">₹<?php echo number_format($plan['price']); ?></span>
                            <small class="text-muted">/year</small>
                            <div class="text-success small">
                                <del>₹<?php echo number_format($plan['price'] * 1.2); ?></del> Save 20%
                            </div>
                        </div>
                        
                        <ul class="list-unstyled text-start">
                            <?php 
                            $features = json_decode($plan['features'], true);
                            foreach ($features as $feature): 
                            ?>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo $feature; ?>
                            </li>
                            <?php endforeach; ?>
                            
                            <li class="mb-2">
                                <i class="fas fa-users text-primary me-2"></i>
                                Up to <?php echo $plan['max_leads']; ?> leads/month
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-file-alt text-primary me-2"></i>
                                <?php echo $plan['max_quotes']; ?> quotes/month
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-receipt text-primary me-2"></i>
                                <?php echo $plan['max_invoices']; ?> invoices/month
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-user-tie text-primary me-2"></i>
                                <?php echo $plan['max_users']; ?> team members
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-cloud text-primary me-2"></i>
                                <?php echo $plan['storage_limit_gb']; ?>GB storage
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-footer bg-transparent">
                        <button class="btn <?php echo $plan['is_popular'] ? 'btn-primary' : 'btn-outline-primary'; ?> w-100" 
                                onclick="subscribeToPlan(<?php echo $plan['id']; ?>, '<?php echo $plan['plan_name']; ?>', <?php echo $plan['price']; ?>)">
                            Start 14-Day Free Trial
                        </button>
                        <small class="text-muted d-block text-center mt-2">No credit card required</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Features Comparison -->
    <div class="mt-5">
        <h2 class="text-center mb-4">Complete Business Management Features</h2>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h4>Lead Management</h4>
                    <p class="text-muted">Capture, track, and convert leads efficiently with automated follow-ups and status tracking.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                    <h4>Quote Generation</h4>
                    <p class="text-muted">Create professional quotes instantly with itemized pricing and customizable templates.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-receipt fa-3x text-primary mb-3"></i>
                    <h4>Invoice Management</h4>
                    <p class="text-muted">Generate invoices, track payments, and manage billing with integrated payment gateway.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-chart-bar fa-3x text-primary mb-3"></i>
                    <h4>Analytics & Reports</h4>
                    <p class="text-muted">Get insights into business performance with detailed analytics and custom reports.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                    <h4>Mobile Optimized</h4>
                    <p class="text-muted">Manage your business on the go with fully responsive mobile interface.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                    <h4>24/7 Support</h4>
                    <p class="text-muted">Get help when you need it with email, phone, and chat support options.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-light rounded p-5 text-center mt-5">
        <h2>Ready to Transform Your Moving Business?</h2>
        <p class="lead">Join hundreds of moving companies who have streamlined their operations with our platform.</p>
        <button class="btn btn-primary btn-lg me-3" onclick="document.getElementById('monthly').checked = true; document.getElementById('monthly').click();">
            View Plans
        </button>
        <a href="/contact" class="btn btn-outline-primary btn-lg">Schedule Demo</a>
    </div>
</div>

<!-- Subscription Modal -->
<div class="modal fade" id="subscriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subscribe to Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="subscriptionForm">
                <div class="modal-body">
                    <input type="hidden" id="selectedPlanId">
                    
                    <div class="mb-3">
                        <label class="form-label">Business Name *</label>
                        <input type="text" class="form-control" id="businessName" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Owner Name *</label>
                            <input type="text" class="form-control" id="ownerName" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="ownerPhone" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="ownerEmail" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Business Address</label>
                        <textarea class="form-control" id="businessAddress" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" id="businessCity">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" id="businessState">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Your 14-day free trial will start immediately. No payment required now.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="subscribeBtn">
                        Start Free Trial
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle between monthly and yearly plans
document.addEventListener('DOMContentLoaded', function() {
    const monthlyRadio = document.getElementById('monthly');
    const yearlyRadio = document.getElementById('yearly');
    const monthlyPlans = document.getElementById('monthly-plans');
    const yearlyPlans = document.getElementById('yearly-plans');
    
    monthlyRadio.addEventListener('change', function() {
        if (this.checked) {
            monthlyPlans.style.display = 'block';
            yearlyPlans.style.display = 'none';
        }
    });
    
    yearlyRadio.addEventListener('change', function() {
        if (this.checked) {
            monthlyPlans.style.display = 'none';
            yearlyPlans.style.display = 'block';
        }
    });
});

function subscribeToPlan(planId, planName, price) {
    document.getElementById('selectedPlanId').value = planId;
    document.querySelector('#subscriptionModal .modal-title').textContent = `Subscribe to ${planName}`;
    
    const modal = new bootstrap.Modal(document.getElementById('subscriptionModal'));
    modal.show();
}

// Handle subscription form submission
document.getElementById('subscriptionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('subscribeBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Trial...';
    
    const formData = {
        plan_id: document.getElementById('selectedPlanId').value,
        business_name: document.getElementById('businessName').value,
        owner_name: document.getElementById('ownerName').value,
        owner_phone: document.getElementById('ownerPhone').value,
        owner_email: document.getElementById('ownerEmail').value,
        business_address: document.getElementById('businessAddress').value,
        business_city: document.getElementById('businessCity').value,
        business_state: document.getElementById('businessState').value
    };
    
    fetch('/api/create-subscription.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/subscription-success?token=' + data.token;
        } else {
            alert('Error: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Start Free Trial';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Start Free Trial';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
