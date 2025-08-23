<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';
require_once '../includes/cashfree-payments.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['plan_id', 'business_name', 'owner_name', 'owner_phone', 'owner_email'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '{$field}' is required"]);
            exit;
        }
    }
    
    // Get plan details
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$input['plan_id']]);
    $plan = $stmt->fetch();
    
    if (!$plan) {
        echo json_encode(['success' => false, 'message' => 'Invalid plan selected']);
        exit;
    }
    
    // Generate organization slug
    $org_slug = strtolower(preg_replace('/[^A-Za-z0-9-]/', '-', $input['business_name'])) . '-' . uniqid();
    
    // Calculate trial dates
    $trial_start = date('Y-m-d');
    $trial_end = date('Y-m-d', strtotime('+14 days'));
    $next_billing = date('Y-m-d', strtotime('+15 days'));
    
    // Create organization record
    $stmt = $pdo->prepare("INSERT INTO organizations 
        (org_name, org_slug, owner_email, owner_phone, address, city, state, 
         subscription_plan_id, subscription_status, subscription_start_date, 
         trial_end_date, next_billing_date, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'trial', ?, ?, ?, NOW())");
    
    $stmt->execute([
        $input['business_name'],
        $org_slug,
        $input['owner_email'],
        $input['owner_phone'],
        $input['business_address'] ?? '',
        $input['business_city'] ?? '',
        $input['business_state'] ?? '',
        $plan['id'],
        $trial_start,
        $trial_end,
        $next_billing
    ]);
    
    $organization_id = $pdo->lastInsertId();
    
    // Create admin user for the organization
    $username = strtolower(str_replace(' ', '', $input['owner_name'])) . '_' . $organization_id;
    $password = 'temp123'; // Temporary password, user will be asked to change
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users 
        (username, email, password_hash, role, is_admin, organization_id, created_at) 
        VALUES (?, ?, ?, 'admin', 1, ?, NOW())");
    
    $stmt->execute([
        $username,
        $input['owner_email'],
        $password_hash,
        $organization_id
    ]);
    
    // Generate access token for immediate login
    $access_token = bin2hex(random_bytes(32));
    
    // Store token for one-time login
    $stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, token_type, expires_at, created_at) VALUES (?, ?, 'trial_access', DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())");
    $stmt->execute([$pdo->lastInsertId(), $access_token]);
    
    // Send welcome email (implement email sending here)
    // send_welcome_email($input['owner_email'], $input['owner_name'], $username, $password, $access_token);
    
    echo json_encode([
        'success' => true,
        'message' => 'Trial account created successfully',
        'token' => $access_token,
        'organization_id' => $organization_id,
        'username' => $username,
        'trial_end_date' => $trial_end
    ]);
    
} catch (Exception $e) {
    error_log("Subscription creation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating your trial account. Please try again.'
    ]);
}
?>
