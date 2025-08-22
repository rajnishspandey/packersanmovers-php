<?php
require_once 'config.php';
$page_title = 'Home';
include 'includes/header.php';
?>

<div class="hero-section text-white text-center py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 text-lg-start">
                <h1 class="display-4 fw-bold mb-4">Moving Shouldn't Be Stressful</h1>
                <p class="lead mb-4">Let our experienced team handle your move with care and efficiency. Whether you're moving locally or long-distance, we provide comprehensive moving services.</p>
                <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                    <a href="quote.php" class="btn btn-primary btn-lg px-4 me-md-2">Get a Free Quote</a>
                    <a href="services.php" class="btn btn-outline-light btn-lg px-4">Our Services</a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="/assets/img/moversanpackerscover.png" alt="Moving services" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row text-center">
        <div class="col-md-12">
            <h2 class="mb-4">Why Choose Our Moving Services?</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="feature-icon bg-primary text-white rounded-circle mb-3">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3 class="h4">Experienced Team</h3>
                    <p>Our trained team handles your belongings with care and attention to detail.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="feature-icon bg-primary text-white rounded-circle mb-3">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <h3 class="h4">Transparent Pricing</h3>
                    <p>Clear pricing with no hidden fees. Get a detailed quote for your moving needs.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="feature-icon bg-primary text-white rounded-circle mb-3">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="h4">Insurance Coverage</h3>
                    <p>Your belongings are protected with our insurance coverage options available.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <img src="/assets/img/moving-services.png" alt="Our moving services" class="img-fluid rounded shadow">
            </div>
            <div class="col-md-6">
                <h2 class="mb-4">Our Comprehensive Moving Services</h2>
                <div class="mb-4">
                    <h4><i class="fas fa-home text-primary me-2"></i>Residential Moving</h4>
                    <p>From apartments to houses, we'll help you move to your new home with ease.</p>
                </div>
                <div class="mb-4">
                    <h4><i class="fas fa-building text-primary me-2"></i>Commercial Moving</h4>
                    <p>Minimize downtime with our efficient office and business relocation services.</p>
                </div>
                <div class="mb-4">
                    <h4><i class="fas fa-box text-primary me-2"></i>Packing Services</h4>
                    <p>Our team will carefully pack your belongings using quality materials.</p>
                </div>
                <a href="services.php" class="btn btn-primary">View All Services</a>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row text-center mb-4">
        <div class="col-md-12">
            <h2>What Our Customers Say</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="card-text">"The team at PackersAnMovers was efficient and careful with our belongings. They helped make our move smoother."</p>
                    <div class="d-flex align-items-center">
                        <div>
                            <h5 class="mb-0">Rajesh Mishra</h5>
                            <small class="text-muted">Local Move</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="card-text">"They moved my office quickly and carefully. There was minimal disruption to our business."</p>
                    <div class="d-flex align-items-center">
                        <div>
                            <h5 class="mb-0">Krishna Yadav</h5>
                            <small class="text-muted">Commercial Move</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                    <p class="card-text">"Their packing service was good. They packed everything with care during our cross-country move."</p>
                    <div class="d-flex align-items-center">
                        <div>
                            <h5 class="mb-0">Shailendra Shukla</h5>
                            <small class="text-muted">Long-Distance Move</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <h2>Ready to Make Your Move Stress-Free?</h2>
                <p class="lead mb-0">Get a free quote today for our moving services.</p>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <a href="quote.php" class="btn btn-light btn-lg">Get Your Free Quote</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>