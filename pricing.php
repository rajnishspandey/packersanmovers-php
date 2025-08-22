<?php
require_once 'config.php';
$page_title = 'Transparent Pricing - ' . get_setting('company_name', 'Professional Packers & Movers');
include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active">Pricing</li>
        </ol>
    </nav>

    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold text-primary">Transparent Pricing</h1>
        <p class="lead text-muted">No Hidden Charges • Competitive Rates • Professional Service</p>
    </div>

    <!-- Pricing Calculator -->
    <div class="card mb-5 border-0 shadow-lg">
        <div class="card-header bg-primary text-white text-center">
            <h3 class="mb-0"><i class="fas fa-calculator me-2"></i>Instant Price Calculator</h3>
        </div>
        <div class="card-body p-4">
            <form id="pricingCalculator">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Home Size</label>
                        <select class="form-select" id="homeSize" required>
                            <option value="">Select Home Size</option>
                            <option value="1bhk" data-base="<?= get_setting('price_1bhk', '8000') ?>">1 BHK</option>
                            <option value="2bhk" data-base="<?= get_setting('price_2bhk', '12000') ?>">2 BHK</option>
                            <option value="3bhk" data-base="<?= get_setting('price_3bhk', '18000') ?>">3 BHK</option>
                            <option value="4bhk" data-base="<?= get_setting('price_4bhk', '25000') ?>">4 BHK</option>
                            <option value="villa" data-base="<?= get_setting('price_villa', '35000') ?>">Villa/Bungalow</option>
                            <option value="office" data-base="<?= get_setting('price_office', '15000') ?>">Office</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Distance (KM)</label>
                        <input type="number" class="form-control" id="distance" placeholder="Enter distance in KM" min="1" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Additional Services</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="packing" data-price="<?= get_setting('price_packing', '2000') ?>">
                            <label class="form-check-label" for="packing">
                                Professional Packing (+₹<?= get_setting('price_packing', '2000') ?>)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="unpacking" data-price="<?= get_setting('price_unpacking', '1500') ?>">
                            <label class="form-check-label" for="unpacking">
                                Unpacking Service (+₹<?= get_setting('price_unpacking', '1500') ?>)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="storage" data-price="<?= get_setting('price_storage', '1000') ?>">
                            <label class="form-check-label" for="storage">
                                Temporary Storage (+₹<?= get_setting('price_storage', '1000') ?>/day)
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Moving Date</label>
                        <input type="date" class="form-control" id="movingDate" min="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="text-center">
                    <button type="button" class="btn btn-primary btn-lg" onclick="calculatePrice()">
                        <i class="fas fa-calculator me-2"></i>Calculate Price
                    </button>
                </div>
            </form>
            
            <div id="priceResult" class="mt-4" style="display: none;">
                <div class="alert alert-success">
                    <h4 class="alert-heading">Estimated Price</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Base Price:</strong> ₹<span id="basePrice">0</span></p>
                            <p><strong>Distance Charges:</strong> ₹<span id="distancePrice">0</span></p>
                            <p><strong>Additional Services:</strong> ₹<span id="additionalPrice">0</span></p>
                        </div>
                        <div class="col-md-6">
                            <h3 class="text-success"><strong>Total: ₹<span id="totalPrice">0</span></strong></h3>
                            <p class="mb-0"><small class="text-muted">*Final price may vary based on actual inventory</small></p>
                        </div>
                    </div>
                    <hr>
                    <a href="/quote" class="btn btn-success">Get Detailed Quote</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Tables -->
    <div class="row mb-5">
        <div class="col-lg-6 mb-4">
            <div class="card h-100 border-0 shadow">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0">Local Moving Rates</h4>
                    <small>Within City (0-50 KM)</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Home Size</th>
                                    <th>Starting Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1 BHK</td>
                                    <td class="fw-bold text-success">₹<?= number_format(get_setting('price_1bhk', '8000')) ?></td>
                                </tr>
                                <tr>
                                    <td>2 BHK</td>
                                    <td class="fw-bold text-success">₹<?= number_format(get_setting('price_2bhk', '12000')) ?></td>
                                </tr>
                                <tr>
                                    <td>3 BHK</td>
                                    <td class="fw-bold text-success">₹<?= number_format(get_setting('price_3bhk', '18000')) ?></td>
                                </tr>
                                <tr>
                                    <td>4 BHK</td>
                                    <td class="fw-bold text-success">₹<?= number_format(get_setting('price_4bhk', '25000')) ?></td>
                                </tr>
                                <tr>
                                    <td>Villa/Bungalow</td>
                                    <td class="fw-bold text-success">₹<?= number_format(get_setting('price_villa', '35000')) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card h-100 border-0 shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Additional Services</h4>
                    <small>Optional Add-ons</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Professional Packing</td>
                                    <td class="fw-bold text-primary">₹<?= number_format(get_setting('price_packing', '2000')) ?></td>
                                </tr>
                                <tr>
                                    <td>Unpacking Service</td>
                                    <td class="fw-bold text-primary">₹<?= number_format(get_setting('price_unpacking', '1500')) ?></td>
                                </tr>
                                <tr>
                                    <td>Temporary Storage</td>
                                    <td class="fw-bold text-primary">₹<?= number_format(get_setting('price_storage', '1000')) ?>/day</td>
                                </tr>
                                <tr>
                                    <td>Insurance Coverage</td>
                                    <td class="fw-bold text-primary">₹<?= number_format(get_setting('price_insurance', '500')) ?></td>
                                </tr>
                                <tr>
                                    <td>Express Delivery</td>
                                    <td class="fw-bold text-primary">₹<?= number_format(get_setting('price_express', '3000')) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Distance Charges -->
    <div class="card mb-5 border-0 shadow">
        <div class="card-header bg-warning text-dark text-center">
            <h4 class="mb-0">Distance-Based Charges</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center mb-3">
                    <div class="border-end">
                        <h5 class="text-success">Local (0-50 KM)</h5>
                        <p class="h4 fw-bold">₹<?= get_setting('rate_local', '15') ?>/KM</p>
                        <small class="text-muted">Within city limits</small>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-3">
                    <div class="border-end">
                        <h5 class="text-primary">Intercity (50-500 KM)</h5>
                        <p class="h4 fw-bold">₹<?= get_setting('rate_intercity', '25') ?>/KM</p>
                        <small class="text-muted">Between cities</small>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-3">
                    <h5 class="text-danger">Long Distance (500+ KM)</h5>
                    <p class="h4 fw-bold">₹<?= get_setting('rate_longdistance', '35') ?>/KM</p>
                    <small class="text-muted">Interstate moves</small>
                </div>
            </div>
        </div>
    </div>

    <!-- What's Included -->
    <div class="row mb-5">
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h4 class="text-success mb-0"><i class="fas fa-check-circle me-2"></i>What's Included</h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Professional moving team</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Loading and unloading</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Transportation vehicle</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Basic wrapping materials</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Furniture disassembly/assembly</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Basic insurance coverage</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Real-time tracking</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h4 class="text-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Additional Charges</h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-info-circle text-warning me-2"></i>Stairs (above 2nd floor): ₹<?= get_setting('charge_stairs', '500') ?>/floor</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-warning me-2"></i>Long carry (>50 meters): ₹<?= get_setting('charge_longcarry', '1000') ?></li>
                        <li class="mb-2"><i class="fas fa-info-circle text-warning me-2"></i>Waiting time: ₹<?= get_setting('charge_waiting', '200') ?>/hour</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-warning me-2"></i>Weekend/Holiday: <?= get_setting('charge_weekend', '20') ?>% extra</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-warning me-2"></i>Fragile items handling: ₹<?= get_setting('charge_fragile', '1500') ?></li>
                        <li class="mb-2"><i class="fas fa-info-circle text-warning me-2"></i>AC/Appliance service: ₹<?= get_setting('charge_appliance', '2000') ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Options -->
    <div class="card mb-5 border-0 shadow">
        <div class="card-header bg-info text-white text-center">
            <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Options</h4>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3 mb-3">
                    <i class="fas fa-money-bill-wave fa-3x text-success mb-2"></i>
                    <h5>Cash</h5>
                    <p class="text-muted">Pay on delivery</p>
                </div>
                <div class="col-md-3 mb-3">
                    <i class="fas fa-university fa-3x text-primary mb-2"></i>
                    <h5>Bank Transfer</h5>
                    <p class="text-muted">Direct bank transfer</p>
                </div>
                <div class="col-md-3 mb-3">
                    <i class="fas fa-credit-card fa-3x text-warning mb-2"></i>
                    <h5>Card Payment</h5>
                    <p class="text-muted">Debit/Credit cards</p>
                </div>
                <div class="col-md-3 mb-3">
                    <i class="fas fa-mobile-alt fa-3x text-info mb-2"></i>
                    <h5>UPI/Wallet</h5>
                    <p class="text-muted">Digital payments</p>
                </div>
            </div>
            <div class="text-center mt-3">
                <p class="text-muted"><strong>Advance Payment:</strong> <?= get_setting('advance_percentage', '25') ?>% advance required to confirm booking</p>
            </div>
        </div>
    </div>

    <!-- Price Guarantee -->
    <div class="text-center bg-light p-4 rounded">
        <h3 class="text-primary mb-3">Our Price Guarantee</h3>
        <div class="row">
            <div class="col-md-4 mb-3">
                <i class="fas fa-handshake fa-3x text-success mb-2"></i>
                <h5>No Hidden Charges</h5>
                <p class="text-muted">Transparent pricing with no surprises</p>
            </div>
            <div class="col-md-4 mb-3">
                <i class="fas fa-shield-alt fa-3x text-primary mb-2"></i>
                <h5>Price Lock Guarantee</h5>
                <p class="text-muted">Quoted price is final (unless scope changes)</p>
            </div>
            <div class="col-md-4 mb-3">
                <i class="fas fa-undo fa-3x text-warning mb-2"></i>
                <h5>Money Back Guarantee</h5>
                <p class="text-muted">100% refund if service not delivered</p>
            </div>
        </div>
    </div>
</div>

<script>
function calculatePrice() {
    const homeSize = document.getElementById('homeSize');
    const distance = parseFloat(document.getElementById('distance').value) || 0;
    const movingDate = document.getElementById('movingDate').value;
    
    if (!homeSize.value || !distance || !movingDate) {
        alert('Please fill all required fields');
        return;
    }
    
    // Base price
    const basePrice = parseInt(homeSize.selectedOptions[0].dataset.base) || 0;
    
    // Distance charges
    let distanceRate = <?= get_setting('rate_local', '15') ?>;
    if (distance > 500) {
        distanceRate = <?= get_setting('rate_longdistance', '35') ?>;
    } else if (distance > 50) {
        distanceRate = <?= get_setting('rate_intercity', '25') ?>;
    }
    const distancePrice = distance * distanceRate;
    
    // Additional services
    let additionalPrice = 0;
    document.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {
        additionalPrice += parseInt(checkbox.dataset.price) || 0;
    });
    
    // Weekend charge
    const selectedDate = new Date(movingDate);
    const isWeekend = selectedDate.getDay() === 0 || selectedDate.getDay() === 6;
    let weekendCharge = 0;
    if (isWeekend) {
        weekendCharge = (basePrice + distancePrice + additionalPrice) * (<?= get_setting('charge_weekend', '20') ?> / 100);
    }
    
    const totalPrice = basePrice + distancePrice + additionalPrice + weekendCharge;
    
    // Display results
    document.getElementById('basePrice').textContent = basePrice.toLocaleString();
    document.getElementById('distancePrice').textContent = distancePrice.toLocaleString();
    document.getElementById('additionalPrice').textContent = (additionalPrice + weekendCharge).toLocaleString();
    document.getElementById('totalPrice').textContent = totalPrice.toLocaleString();
    
    document.getElementById('priceResult').style.display = 'block';
    document.getElementById('priceResult').scrollIntoView({ behavior: 'smooth' });
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "PriceSpecification",
  "description": "Transparent pricing for professional moving services",
  "priceCurrency": "INR",
  "price": "<?= get_setting('price_1bhk', '8000') ?>",
  "minPrice": "<?= get_setting('price_1bhk', '8000') ?>",
  "maxPrice": "<?= get_setting('price_villa', '35000') ?>",
  "offers": {
    "@type": "Offer",
    "description": "Professional moving services with transparent pricing",
    "seller": {
      "@type": "MovingCompany",
      "name": "<?= get_setting('company_name', 'Professional Packers & Movers') ?>"
    },
    "priceValidUntil": "<?= date('Y-12-31') ?>",
    "availability": "https://schema.org/InStock"
  }
}
</script>

<?php include 'includes/footer.php'; ?>
