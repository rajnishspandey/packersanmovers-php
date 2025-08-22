<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

if (empty($uri)) {
    include 'index.php';
    return;
}

// Handle specific routes
switch ($uri) {
    case 'dashboard':
        include 'dashboard.php';
        return;
    case 'admin':
        include 'admin.php';
        return;
    case 'leads':
        include 'leads.php';
        return;
    case 'quotes':
        include 'quotes.php';
        return;
    case 'invoices':
        include 'invoices.php';
        return;
    case 'analytics':
        include 'analytics.php';
        return;
    case 'pmlogin':
        include 'pmlogin.php';
        return;


    case 'settings':
        include 'settings.php';
        return;
    case 'estimate':
        include 'estimate.php';
        return;
    case 'invoice':
        include 'invoice.php';
        return;
    case 'thank-you':
        include 'thank-you.php';
        return;
    case 'test-email':
        include 'test-email.php';
        return;
    case 'create-admin':
        include 'create-admin.php';
        return;
    case 'run-migration':
        include 'run-migration.php';
        return;
    case 'business-management':
        include 'business-management.php';
        return;


}

$file = $uri . '.php';
if (file_exists($file)) {
    include $file;
    return;
}

return false;
?>