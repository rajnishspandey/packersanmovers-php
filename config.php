<?php
// Load environment variables
function loadEnv($file) {
    if (!file_exists($file)) return;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv('.env');

// Database configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'packersanmovers');
define('DB_USER', $_ENV['DB_USER'] ?? 'your_db_user');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'your_db_password');





// Start session with 1 hour timeout
session_start();

// Check for session timeout (1 hour = 3600 seconds)
if (isset($_SESSION['user_id'])) {
    $timeout = 3600; // 1 hour
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        // Session expired
        session_unset();
        session_destroy();
        if (!in_array(basename($_SERVER['PHP_SELF']), ['pmlogin.php', 'index.php', 'about.php', 'services.php', 'contact.php'])) {
            header('Location: /pmlogin?timeout=1');
            exit();
        }
    } else {
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
}

// Get support emails from database
function get_support_emails() {
    global $pdo;
    $emails_string = get_setting('support_emails', 'support@packersanmovers.com');
    return explode(',', $emails_string);
}

// Database connection - MySQL only
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test connection
    $pdo->query("SELECT 1");
} catch(PDOException $e) {
    // Log the error for debugging
    error_log("MySQL Connection Failed: " . $e->getMessage());
    error_log("DSN: mysql:host=" . DB_HOST . ";dbname=" . DB_NAME);
    error_log("User: " . DB_USER);
    
    die("Database connection failed. Please check your .env configuration.<br>" . 
        "Error: " . $e->getMessage() . "<br>" .
        "Visit /debug-db to troubleshoot.");
}

// Website and Email configuration (from database) - after PDO connection
define('WEBSITE_NAME', get_setting('website_name', ''));
define('WEBSITE_URL', get_setting('website_url', ''));
define('MAIL_HOST', get_setting('mail_host', ''));
define('MAIL_PORT', get_setting('mail_port', '587'));
define('MAIL_USERNAME', get_setting('mail_username', ''));
define('MAIL_PASSWORD', get_setting('mail_password', ''));
define('MAIL_FROM', get_setting('mail_from', ''));

// Helper functions
function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/pmlogin');
    }
}

function get_default_dashboard() {
    // Always check admin status first
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        return '/admin';
    }
    
    // Check user role directly
    $role = get_user_role();
    if ($role === 'admin' || $role === 'manager' || $role === 'sales' || $role === 'accounts') {
        return '/admin';
    }
    
    return '/dashboard'; // Only read access (viewer), send to limited dashboard
}

// Get status configurations from database
function get_statuses($type) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM statuses WHERE entity_type = ? AND is_active = 1 ORDER BY sort_order");
    $stmt->execute([$type]);
    return $stmt->fetchAll();
}

function get_status_info($type, $status_key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM statuses WHERE entity_type = ? AND status_key = ?");
    $stmt->execute([$type, $status_key]);
    return $stmt->fetch();
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        redirect('/pmlogin');
    }
}

// Get user role and permissions
function get_user_role() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) return null;
    
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn();
}

function get_user_permissions($role = null) {
    global $pdo;
    if (!$role) $role = get_user_role();
    if (!$role) return [];
    
    $stmt = $pdo->prepare("SELECT permissions FROM roles WHERE role_name = ?");
    $stmt->execute([$role]);
    $permissions = $stmt->fetchColumn();
    return $permissions ? json_decode($permissions, true) : [];
}

function has_permission($module, $action = 'read') {
    if (is_admin()) return true; // Admin has all permissions
    
    $permissions = get_user_permissions();
    if (!isset($permissions[$module])) return false;
    
    $module_perm = $permissions[$module];
    if ($module_perm === 'none') return false;
    if ($module_perm === 'crud') return true;
    if ($module_perm === 'read' && $action === 'read') return true;
    if ($module_perm === 'crud' && in_array($action, ['create', 'update', 'delete'])) return true;
    
    return false;
}

function require_permission($module, $action = 'read') {
    require_login();
    if (!has_permission($module, $action)) {
        $_SESSION['error'] = 'Access denied. Insufficient permissions.';
        redirect(get_default_dashboard());
    }
}

// Get all roles for dropdown
function get_all_roles() {
    global $pdo;
    $stmt = $pdo->query("SELECT role_name, role_label FROM roles ORDER BY role_name");
    return $stmt->fetchAll();
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to get setting value
function get_setting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch(Exception $e) {
        return $default;
    }
}

// Function to update setting
function update_setting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    return $stmt->execute([$key, $value]);
}

// Generate secure token for public access
function generate_secure_token() {
    return bin2hex(random_bytes(32)); // 64 character secure token
}

// Get quote by secure token (for public access)
function get_quote_by_token($token) {
    global $pdo;
    if (empty($token) || strlen($token) !== 64) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT q.*, l.name, l.email, l.phone, l.move_from, l.move_to, l.move_date, l.home_size 
                          FROM quotes q 
                          JOIN leads l ON q.lead_id = l.id 
                          WHERE q.secure_token = ? AND q.status != 'draft'");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

// Get invoice by secure token (for public access)
function get_invoice_by_token($token) {
    global $pdo;
    if (empty($token) || strlen($token) !== 64) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT i.*, q.quote_number, l.name, l.email, l.phone, l.move_from, l.move_to, l.move_date, l.home_size 
                          FROM invoices i 
                          JOIN quotes q ON i.quote_id = q.id 
                          JOIN leads l ON q.lead_id = l.id 
                          WHERE i.secure_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch();
}


?>