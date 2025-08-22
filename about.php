<?php
require_once 'config.php';
$page_title = 'About Us';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-center mb-5">About PackersAnMovers</h1>
        </div>
    </div>

    <div class="row align-items-center mb-5">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <img src="/assets/img/about-team.jpg" alt="Our Team" class="img-fluid rounded shadow">
        </div>
        <div class="col-lg-6">
            <h2 class="mb-4">Your Trusted Moving Partner</h2>
            <p class="lead">With years of experience in the moving industry, PackersAnMovers is a moving company serving customers across India.</p>
            <p>We understand that moving can be stressful. That's why we're committed to making your relocation smoother. Our experienced team handles aspects of your move with care and attention to detail.</p>
            <p>From residential moves to commercial relocations, we have experience and resources to handle moves of various sizes. We focus on customer satisfaction and service quality.</p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-lg-12">
            <h2 class="text-center mb-4">Our Mission & Vision</h2>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-bullseye fa-3x text-primary mb-3"></i>
                    <h3>Our Mission</h3>
                    <p>To provide moving and packing services that meet customer expectations while focusing on the safe transportation of their belongings.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-eye fa-3x text-primary mb-3"></i>
                    <h3>Our Vision</h3>
                    <p>To be a trusted moving company in India, known for our reliability and commitment to customer satisfaction.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <h2>Ready to Experience Stress-Free Moving?</h2>
                <p class="lead mb-0">Join thousands of satisfied customers who have trusted us with their moves. Get your free quote today!</p>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <a href="quote.php" class="btn btn-primary btn-lg">Get Free Quote</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>