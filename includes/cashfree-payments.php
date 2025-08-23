<?php
/**
 * Cashfree Payment Processing Functions
 */

require_once 'cashfree-config.php';

/**
 * Create One-time Payment Order
 */
function create_cashfree_payment_order($customer_data, $amount, $currency = 'INR', $order_note = null) {
    try {
        $order_id = generate_cashfree_order_id();
        
        $order_data = [
            'order_id' => $order_id,
            'order_amount' => floatval($amount),
            'order_currency' => $currency,
            'order_note' => $order_note ?? 'Payment for Packers & Movers Service',
            'customer_details' => [
                'customer_id' => 'CUST_' . time() . '_' . uniqid(),
                'customer_name' => $customer_data['name'],
                'customer_email' => $customer_data['email'],
                'customer_phone' => $customer_data['phone']
            ],
            'order_meta' => [
                'return_url' => CASHFREE_RETURN_URL,
                'notify_url' => CASHFREE_NOTIFY_URL
            ]
        ];
        
        $response = make_cashfree_request('/orders', 'POST', $order_data);
        
        // Store order in database
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO cashfree_orders (order_id, cf_order_id, customer_email, amount, currency, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $order_id,
            $response['cf_order_id'] ?? $order_id,
            $customer_data['email'],
            $amount,
            $currency,
            'ACTIVE'
        ]);
        
        return [
            'success' => true,
            'order_id' => $order_id,
            'cf_order_id' => $response['cf_order_id'],
            'payment_session_id' => $response['payment_session_id'],
            'checkout_url' => CASHFREE_API_BASE . '/checkout?payment_session_id=' . $response['payment_session_id']
        ];
        
    } catch (Exception $e) {
        error_log("Cashfree Order Creation Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Create Subscription Plan
 */
function create_cashfree_subscription($customer_data, $plan_data) {
    try {
        $subscription_id = generate_cashfree_subscription_id();
        
        $subscription_data = [
            'subscription_id' => $subscription_id,
            'customer_details' => [
                'customer_name' => $customer_data['name'],
                'customer_email' => $customer_data['email'],
                'customer_phone' => $customer_data['phone']
            ],
            'plan_details' => [
                'plan_name' => $plan_data['plan_name'],
                'plan_type' => 'PERIODIC',
                'plan_amount' => floatval($plan_data['amount']),
                'plan_max_amount' => floatval($plan_data['max_amount'] ?? $plan_data['amount'] * 2),
                'plan_max_cycles' => intval($plan_data['max_cycles'] ?? 0), // 0 = unlimited
                'plan_intervals' => intval($plan_data['intervals'] ?? 1),
                'plan_interval_type' => strtoupper($plan_data['interval_type'] ?? 'MONTH'),
                'plan_currency' => 'INR'
            ],
            'authorization_details' => [
                'authorization_amount' => 1.0, // Re 1 for authorization
                'authorization_amount_refund' => true,
                'payment_methods' => ['card', 'upi', 'netbanking']
            ],
            'subscription_meta' => [
                'return_url' => get_setting('website_url', 'https://your-domain.com') . '/subscription-callback',
                'notification_channel' => ['EMAIL', 'SMS']
            ],
            'subscription_expiry_time' => date('Y-m-d\TH:i:s\Z', strtotime('+1 year')),
            'subscription_first_charge_time' => date('Y-m-d\TH:i:s\Z', strtotime('+1 day'))
        ];
        
        $response = make_cashfree_request('/subscriptions', 'POST', $subscription_data);
        
        // Store subscription in database
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO subscriptions (subscription_id, cf_subscription_id, organization_id, plan_id, customer_email, amount, billing_cycle, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $subscription_id,
            $response['cf_subscription_id'] ?? $subscription_id,
            $customer_data['organization_id'] ?? null,
            $plan_data['plan_id'] ?? null,
            $customer_data['email'],
            $plan_data['amount'],
            $plan_data['interval_type'] ?? 'monthly',
            'INITIALIZED'
        ]);
        
        return [
            'success' => true,
            'subscription_id' => $subscription_id,
            'cf_subscription_id' => $response['cf_subscription_id'],
            'authorization_url' => $response['subscription_session_id'],
            'status' => $response['subscription_status']
        ];
        
    } catch (Exception $e) {
        error_log("Cashfree Subscription Creation Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Fetch Payment Status
 */
function get_cashfree_payment_status($order_id) {
    try {
        $response = make_cashfree_request("/orders/{$order_id}");
        
        // Update local database
        global $pdo;
        $stmt = $pdo->prepare("UPDATE cashfree_orders SET status = ?, updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$response['order_status'], $order_id]);
        
        return [
            'success' => true,
            'status' => $response['order_status'],
            'amount' => $response['order_amount'],
            'currency' => $response['order_currency']
        ];
        
    } catch (Exception $e) {
        error_log("Cashfree Payment Status Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Fetch Subscription Status
 */
function get_cashfree_subscription_status($subscription_id) {
    try {
        $response = make_cashfree_request("/subscriptions/{$subscription_id}");
        
        // Update local database
        global $pdo;
        $stmt = $pdo->prepare("UPDATE subscriptions SET status = ?, updated_at = NOW() WHERE subscription_id = ?");
        $stmt->execute([$response['subscription_status'], $subscription_id]);
        
        return [
            'success' => true,
            'status' => $response['subscription_status'],
            'next_payment_date' => $response['next_payment_time'] ?? null,
            'current_cycle' => $response['current_cycle'] ?? 0
        ];
        
    } catch (Exception $e) {
        error_log("Cashfree Subscription Status Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Cancel Subscription
 */
function cancel_cashfree_subscription($subscription_id) {
    try {
        $response = make_cashfree_request("/subscriptions/{$subscription_id}/cancel", 'POST');
        
        // Update local database
        global $pdo;
        $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'CANCELLED', cancelled_at = NOW(), updated_at = NOW() WHERE subscription_id = ?");
        $stmt->execute([$subscription_id]);
        
        return [
            'success' => true,
            'message' => 'Subscription cancelled successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Cashfree Subscription Cancellation Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Process Refund
 */
function process_cashfree_refund($order_id, $refund_amount = null, $refund_note = null) {
    try {
        $refund_data = [
            'refund_amount' => floatval($refund_amount),
            'refund_id' => 'REFUND_' . time() . '_' . uniqid(),
            'refund_note' => $refund_note ?? 'Refund processed'
        ];
        
        $response = make_cashfree_request("/orders/{$order_id}/refunds", 'POST', $refund_data);
        
        // Store refund record
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO payment_refunds (order_id, refund_id, cf_refund_id, amount, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $order_id,
            $refund_data['refund_id'],
            $response['cf_refund_id'] ?? '',
            $refund_amount,
            'PENDING',
            $refund_note
        ]);
        
        return [
            'success' => true,
            'refund_id' => $refund_data['refund_id'],
            'cf_refund_id' => $response['cf_refund_id'],
            'status' => 'PENDING'
        ];
        
    } catch (Exception $e) {
        error_log("Cashfree Refund Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
