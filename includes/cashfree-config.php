<?php
/**
 * Cashfree Payment Gateway Configuration
 * 
 * IMPORTANT: Replace these placeholder values with your actual Cashfree credentials
 * 
 * To get your credentials:
 * 1. Login to Cashfree Dashboard: https://merchant.cashfree.com/
 * 2. Navigate to Payment Gateway Dashboard
 * 3. Go to Developers > API Keys
 * 4. Copy your Client ID and Client Secret
 * 
 * For Sandbox Testing:
 * - Use test credentials from sandbox environment
 * - API Endpoint: https://sandbox.cashfree.com/pg
 * 
 * For Production:
 * - Use live credentials from production environment  
 * - API Endpoint: https://api.cashfree.com/pg
 */

// Cashfree Configuration
define('CASHFREE_CLIENT_ID', $_ENV['CASHFREE_CLIENT_ID'] ?? 'CF_SANDBOX_CLIENT_ID_PLACEHOLDER');
define('CASHFREE_CLIENT_SECRET', $_ENV['CASHFREE_CLIENT_SECRET'] ?? 'CF_SANDBOX_SECRET_PLACEHOLDER');
define('CASHFREE_ENVIRONMENT', $_ENV['CASHFREE_ENV'] ?? 'sandbox'); // 'sandbox' or 'production'

// API Endpoints
if (CASHFREE_ENVIRONMENT === 'production') {
    define('CASHFREE_API_BASE', 'https://api.cashfree.com/pg');
} else {
    define('CASHFREE_API_BASE', 'https://sandbox.cashfree.com/pg');
}

// Cashfree API Version
define('CASHFREE_API_VERSION', '2023-08-01');

// Website URLs for callbacks
define('CASHFREE_RETURN_URL', get_setting('website_url', 'https://your-domain.com') . '/payment-callback');
define('CASHFREE_NOTIFY_URL', get_setting('website_url', 'https://your-domain.com') . '/webhook/cashfree');

/**
 * Generate Cashfree API Headers
 */
function get_cashfree_headers($include_content_type = true) {
    $headers = [
        'x-client-id: ' . CASHFREE_CLIENT_ID,
        'x-client-secret: ' . CASHFREE_CLIENT_SECRET,
        'x-api-version: ' . CASHFREE_API_VERSION
    ];
    
    if ($include_content_type) {
        $headers[] = 'Content-Type: application/json';
    }
    
    return $headers;
}

/**
 * Make Cashfree API Request
 */
function make_cashfree_request($endpoint, $method = 'GET', $data = null) {
    $url = CASHFREE_API_BASE . $endpoint;
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => get_cashfree_headers(),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        throw new Exception("Cashfree API Request Failed: " . $error);
    }
    
    $decoded_response = json_decode($response, true);
    
    if ($http_code >= 400) {
        $error_msg = $decoded_response['message'] ?? 'Unknown API Error';
        throw new Exception("Cashfree API Error (HTTP $http_code): " . $error_msg);
    }
    
    return $decoded_response;
}

/**
 * Verify Cashfree Webhook Signature
 */
function verify_cashfree_webhook($payload, $signature, $timestamp) {
    $signed_payload = $timestamp . '.' . $payload;
    $expected_signature = base64_encode(hash_hmac('sha256', $signed_payload, CASHFREE_CLIENT_SECRET, true));
    
    return hash_equals($signature, $expected_signature);
}

/**
 * Generate Cashfree Order ID
 */
function generate_cashfree_order_id($prefix = 'ORDER') {
    return $prefix . '_' . time() . '_' . uniqid();
}

/**
 * Generate Cashfree Subscription ID
 */
function generate_cashfree_subscription_id($prefix = 'SUB') {
    return $prefix . '_' . time() . '_' . uniqid();
}
?>
