<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Dynamic Title -->
    <title>PackersAnMovers | Hassle-Free Relocation Services Near You | <?php echo isset($page_title) ? $page_title : 'Home'; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Experience stress-free moving with our expert Packers and Movers services. Local, interstate, and office shifting solutions at affordable prices. Get a free quote today! | PackersAnMovers">
    <meta name="keywords" content="packers and movers, relocation services, home shifting, office relocation, moving services, packing services, moving company, affordable movers, best packers, moving near me, packers and movers near me, packersanmovers">
    <meta name="author" content="PackersAnMovers">
    <link rel="canonical" href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Facebook / LinkedIn -->
    <meta property="og:title" content="PackersAnMovers | Hassle-Free Relocation Services Near You">
    <meta property="og:description" content="Experience stress-free moving with our expert Packers and Movers services. Local, interstate, and office shifting solutions at affordable prices. Get a free quote today!">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="<?php echo get_setting('website_url', 'https://www.packersanmovers.com'); ?>/assets/img/og-image.jpg">
    <meta property="og:site_name" content="<?php echo get_setting('company_name', 'PackersAnMovers'); ?>">
    
    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo get_setting('company_name', 'PackersAnMovers'); ?> | Hassle-Free Relocation Services Near You">
    <meta name="twitter:description" content="Experience stress-free moving with our expert Packers and Movers services. Local, interstate, and office shifting solutions at affordable prices. Get a free quote today!">
    <meta name="twitter:image" content="<?php echo get_setting('website_url', 'https://www.packersanmovers.com'); ?>/assets/img/og-image.jpg">
    <meta name="twitter:site" content="@packersanmovers">
    <meta name="twitter:creator" content="@packersanmovers">
    
    <!-- WhatsApp / Slack / Others -->
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon.ico">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- ======== Default Consent Denied (Place in <head> before GTM) ======== -->
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('consent', 'default', {
        'ad_storage': 'granted',
        'ad_user_data': 'granted',
        'ad_personalization': 'granted',
        'analytics_storage': 'granted'
    });
    </script>

    <!-- ======== Google Tag Manager ======== -->
    <script>
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-KQVFV8F8');
    </script>
    <!-- ======== End Google Tag Manager ======== -->
</head>
<body>
    <!-- Google Tag Manager (noscript) -->
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KQVFV8F8"
        height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
    <!-- End Google Tag Manager (noscript) -->


    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-truck-moving me-2"></i><?php echo get_setting('company_name', 'PackersAnMovers'); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contact">Contact</a>
                    </li>
                </ul>
            </div>
            
            <!-- Business menu outside collapsible area -->
            <div class="d-flex align-items-center">
                <?php if (is_logged_in()): ?>
                <div class="dropdown me-2">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" id="businessDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <span class="d-none d-md-inline"><?php echo $_SESSION['username']; ?></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/admin"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="/leads"><i class="fas fa-users"></i> Leads</a></li>
                        <li><a class="dropdown-item" href="/quotes"><i class="fas fa-file-alt"></i> Quotes</a></li>
                        <li><a class="dropdown-item" href="/invoices"><i class="fas fa-receipt"></i> Invoices</a></li>
                        <li><a class="dropdown-item" href="/business-expenses"><i class="fas fa-exchange-alt"></i> Business Transactions</a></li>
                        <li><a class="dropdown-item" href="/analytics"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                        <li><a class="dropdown-item" href="/business-management"><i class="fas fa-building"></i> Business Mgmt</a></li>
                        <?php if (is_admin()): ?>
                        <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog"></i> Settings</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <a href="/quote" class="btn btn-light">Get a Free Quote</a>
            </div>
        </div>
    </nav>

    <main>
        <?php if (isset($_SESSION['message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-info">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        </div>
        <?php endif; ?>