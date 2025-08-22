<?php
// Web interface for running migration
require_once 'config.php';
require_login();

if (!is_admin()) {
    die('Access denied. Admin only.');
}

$page_title = 'Database Migration';
include 'includes/header.php';

if ($_POST['action'] ?? '' === 'run_migration') {
    echo '<div class="container mt-4">';
    echo '<div class="card">';
    echo '<div class="card-header"><h5>Migration Results</h5></div>';
    echo '<div class="card-body">';
    echo '<pre style="background: #000; color: #0f0; padding: 15px; border-radius: 5px;">';
    
    // Capture output
    ob_start();
    include 'migrate.php';
    $output = ob_get_clean();
    
    echo htmlspecialchars($output);
    echo '</pre>';
    echo '<a href="/run-migration" class="btn btn-secondary">Back</a>';
    echo '</div></div></div>';
} else {
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-database"></i> Database Migration</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <h6><i class="fas fa-exclamation-triangle"></i> Important!</h6>
                <p>This migration will:</p>
                <ul>
                    <li><strong>Add missing columns:</strong>
                        <ul>
                            <li>quotes: service_rate, service_amount, tax_enabled, secure_token</li>
                            <li>invoices: service_rate, service_amount, tax_enabled, secure_token</li>
                            <li>leads: thank_you_token, thank_you_shown</li>
                        </ul>
                    </li>
                    <li><strong>Add missing settings:</strong> gst_number, company_tagline</li>
                    <li><strong>Fix existing invoices:</strong> Copy tax fields from quotes to show proper breakdown</li>
                    <li><strong>Add lead statuses:</strong> Completed and Sent statuses for leads</li>
                </ul>
                <p><strong>✅ Safe to run multiple times</strong> - Won't break existing data</p>
                <p><strong>⚠️ Focus:</strong> Only fixes invoice tax display issue</p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="run_migration">
                <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to run the database migration?')">
                    <i class="fas fa-play"></i> Run Migration
                </button>
                <a href="/admin" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php
}
include 'includes/footer.php';
?>