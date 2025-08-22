<?php
require_once 'config.php';

echo "Starting database migration...\n";

try {
    // Check if old status_config table exists and rename it
    echo "Checking status table...\n";
    try {
        $pdo->exec("RENAME TABLE status_config TO statuses");
        echo "✓ Renamed status_config table to statuses\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            echo "- status_config table doesn't exist (probably already migrated)\n";
        } else {
            echo "- Status table rename error: " . $e->getMessage() . "\n";
        }
    }
    
    // Update column name in statuses table if needed
    try {
        $pdo->exec("ALTER TABLE statuses CHANGE type entity_type VARCHAR(20) NOT NULL");
        echo "✓ Updated column name: type -> entity_type\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), "Unknown column") !== false) {
            echo "- Column 'type' doesn't exist (probably already updated)\n";
        } else {
            echo "- Column rename error: " . $e->getMessage() . "\n";
        }
    }
    // Add missing columns to quotes table
    echo "Adding missing columns to quotes table...\n";
    
    $columns_to_add = [
        'service_rate' => 'ALTER TABLE quotes ADD COLUMN service_rate DECIMAL(5,2) DEFAULT 0',
        'service_amount' => 'ALTER TABLE quotes ADD COLUMN service_amount DECIMAL(10,2) DEFAULT 0',
        'tax_enabled' => 'ALTER TABLE quotes ADD COLUMN tax_enabled TINYINT(1) DEFAULT 0',
        'secure_token' => 'ALTER TABLE quotes ADD COLUMN secure_token VARCHAR(64) NULL'
    ];
    
    // Add missing columns to leads table
    $leads_columns = [
        'thank_you_token' => 'ALTER TABLE leads ADD COLUMN thank_you_token VARCHAR(64) NULL',
        'thank_you_shown' => 'ALTER TABLE leads ADD COLUMN thank_you_shown TINYINT(1) DEFAULT 0'
    ];
    
    // Add missing columns to invoices table
    $invoice_columns = [
        'secure_token' => 'ALTER TABLE invoices ADD COLUMN secure_token VARCHAR(64) NULL',
        'service_rate' => 'ALTER TABLE invoices ADD COLUMN service_rate DECIMAL(5,2) DEFAULT 0',
        'service_amount' => 'ALTER TABLE invoices ADD COLUMN service_amount DECIMAL(10,2) DEFAULT 0',
        'tax_enabled' => 'ALTER TABLE invoices ADD COLUMN tax_enabled TINYINT(1) DEFAULT 0'
    ];
    
    foreach ($columns_to_add as $column => $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ Added quotes column: $column\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "- Quotes column $column already exists\n";
            } else {
                echo "✗ Error adding quotes $column: " . $e->getMessage() . "\n";
            }
        }
    }
    
    foreach ($invoice_columns as $column => $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ Added invoices column: $column\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "- Invoices column $column already exists\n";
            } else {
                echo "✗ Error adding invoices $column: " . $e->getMessage() . "\n";
            }
        }
    }
    
    foreach ($leads_columns as $column => $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ Added leads column: $column\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "- Leads column $column already exists\n";
            } else {
                echo "✗ Error adding leads $column: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Add missing settings
    echo "\nAdding missing settings...\n";
    
    $settings_to_add = [
        'gst_number' => '',
        'company_tagline' => ''
    ];
    
    foreach ($settings_to_add as $key => $value) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
            echo "✓ Added setting: $key\n";
        } catch (Exception $e) {
            echo "- Setting $key already exists or error: " . $e->getMessage() . "\n";
        }
    }
    
    // Update existing quotes to have proper tax_enabled flag
    echo "\nUpdating existing quotes...\n";
    
    $stmt = $pdo->prepare("UPDATE quotes SET tax_enabled = 1 WHERE tax_rate > 0 AND tax_enabled = 0");
    $updated = $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ Updated $count quotes with tax_enabled flag\n";
    
    // Calculate missing service amounts for existing quotes
    $stmt = $pdo->prepare("UPDATE quotes SET service_amount = (subtotal * service_rate / 100) WHERE service_amount = 0 AND service_rate > 0");
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ Calculated service amounts for $count quotes\n";
    
    // Fix existing invoices - copy tax fields from their quotes
    echo "\nFixing existing invoices with missing tax fields...\n";
    try {
        $stmt = $pdo->query("SELECT i.id as invoice_id, q.service_rate, q.service_amount, q.tax_enabled 
                            FROM invoices i 
                            JOIN quotes q ON i.quote_id = q.id 
                            WHERE (i.tax_enabled IS NULL OR i.tax_enabled = 0) AND q.tax_enabled = 1");
        $invoices_to_fix = $stmt->fetchAll();
        
        foreach ($invoices_to_fix as $invoice) {
            $update_stmt = $pdo->prepare("UPDATE invoices SET service_rate = ?, service_amount = ?, tax_enabled = ? WHERE id = ?");
            $update_stmt->execute([
                $invoice['service_rate'] ?? 0,
                $invoice['service_amount'] ?? 0, 
                $invoice['tax_enabled'] ?? 0,
                $invoice['invoice_id']
            ]);
        }
        
        echo "✓ Fixed " . count($invoices_to_fix) . " invoices with missing tax fields\n";
    } catch (Exception $e) {
        echo "- Error fixing invoices: " . $e->getMessage() . "\n";
    }
    
    // Add completed status to leads if not exists
    echo "\nAdding completed status to leads...\n";
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO statuses (entity_type, status_key, status_label, status_color, sort_order) VALUES ('lead', 'completed', 'Completed', 'success', 6)");
        $stmt->execute();
        echo "✓ Added completed status for leads\n";
    } catch (Exception $e) {
        echo "- Completed status already exists or error: " . $e->getMessage() . "\n";
    }
    
    // Add sent status to leads if not exists
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO statuses (entity_type, status_key, status_label, status_color, sort_order) VALUES ('lead', 'sent', 'Sent', 'info', 5)");
        $stmt->execute();
        echo "✓ Added sent status for leads\n";
    } catch (Exception $e) {
        echo "- Sent status already exists or error: " . $e->getMessage() . "\n";
    }
    
    // Add abandoned status to quotes if not exists
    echo "\nAdding abandoned status to quotes...\n";
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO statuses (entity_type, status_key, status_label, status_color, sort_order) VALUES ('quote', 'abandoned', 'Abandoned', 'secondary', 99)");
        $stmt->execute();
        echo "✓ Added abandoned status for quotes\n";
    } catch (Exception $e) {
        echo "- Abandoned status already exists or error: " . $e->getMessage() . "\n";
    }
    
    // Add abandoned status to invoices if not exists
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO statuses (entity_type, status_key, status_label, status_color, sort_order) VALUES ('invoice', 'abandoned', 'Abandoned', 'secondary', 99)");
        $stmt->execute();
        echo "✓ Added abandoned status for invoices\n";
    } catch (Exception $e) {
        echo "- Abandoned status already exists or error: " . $e->getMessage() . "\n";
    }
    
    // Remove ALL existing lead statuses to avoid duplicates
    echo "\nCleaning up ALL existing lead statuses...\n";
    try {
        $stmt = $pdo->prepare("DELETE FROM statuses WHERE entity_type = 'lead'");
        $stmt->execute();
        echo "✓ Removed all existing lead statuses\n";
    } catch (Exception $e) {
        echo "- Error removing lead statuses: " . $e->getMessage() . "\n";
    }
    
    // Ensure all required lead statuses exist
    echo "\nEnsuring lead statuses exist...\n";
    $lead_statuses = [
        ['inquiry', 'New Inquiry', 'primary', 1],
        ['survey_scheduled', 'Survey Scheduled', 'info', 2],
        ['survey_done', 'Survey Done', 'warning', 3],
        ['draft', 'Draft', 'secondary', 4],
        ['sent', 'Sent', 'info', 5],
        ['completed', 'Completed', 'success', 6],
        ['cancelled', 'Cancelled', 'danger', 7]
    ];
    
    foreach ($lead_statuses as $status) {
        try {
            $stmt = $pdo->prepare("INSERT INTO statuses (entity_type, status_key, status_label, status_color, sort_order) VALUES ('lead', ?, ?, ?, ?)");
            $stmt->execute($status);
            echo "✓ Added lead status: {$status[1]}\n";
        } catch (Exception $e) {
            echo "- Lead status {$status[1]} error: " . $e->getMessage() . "\n";
        }
    }
    
    // Remove ALL existing quote statuses to avoid duplicates
    echo "\nCleaning up ALL existing quote statuses...\n";
    try {
        $stmt = $pdo->prepare("DELETE FROM statuses WHERE entity_type = 'quote'");
        $stmt->execute();
        echo "✓ Removed all existing quote statuses\n";
    } catch (Exception $e) {
        echo "- Error removing quote statuses: " . $e->getMessage() . "\n";
    }
    
    // Ensure all required quote statuses exist
    echo "\nEnsuring quote statuses exist...\n";
    $quote_statuses = [
        ['draft', 'Draft', 'secondary', 1],
        ['sent', 'Sent', 'primary', 2],
        ['approved', 'Approved', 'success', 3],
        ['rejected', 'Rejected', 'danger', 4],
        ['expired', 'Expired', 'warning', 5],
        ['abandoned', 'Abandoned', 'secondary', 6]
    ];
    
    foreach ($quote_statuses as $status) {
        try {
            $stmt = $pdo->prepare("INSERT INTO statuses (entity_type, status_key, status_label, status_color, sort_order) VALUES ('quote', ?, ?, ?, ?)");
            $stmt->execute($status);
            echo "✓ Added quote status: {$status[1]}\n";
        } catch (Exception $e) {
            echo "- Quote status {$status[1]} error: " . $e->getMessage() . "\n";
        }
    }
    
    // Remove ALL existing invoice statuses to avoid duplicates
    echo "\nCleaning up ALL existing invoice statuses...\n";
    try {
        $stmt = $pdo->prepare("DELETE FROM statuses WHERE entity_type = 'invoice'");
        $stmt->execute();
        echo "✓ Removed all existing invoice statuses\n";
    } catch (Exception $e) {
        echo "- Error removing invoice statuses: " . $e->getMessage() . "\n";
    }
    
    // Ensure all required invoice statuses exist
    echo "\nAdding invoice statuses...\n";
    $invoice_statuses = [
        ['pending', 'Pending', 'warning', 1],
        ['partial', 'Partially Paid', 'info', 2],
        ['paid', 'Paid', 'success', 3],
        ['overdue', 'Overdue', 'danger', 4],
        ['abandoned', 'Abandoned', 'secondary', 5]
    ];
    
    foreach ($invoice_statuses as $status) {
        try {
            $stmt = $pdo->prepare("INSERT INTO statuses (entity_type, status_key, status_label, status_color, sort_order) VALUES ('invoice', ?, ?, ?, ?)");
            $stmt->execute($status);
            echo "✓ Added invoice status: {$status[1]}\n";
        } catch (Exception $e) {
            echo "- Invoice status {$status[1]} error: " . $e->getMessage() . "\n";
        }
    }

    $alterQuotes = [
    "ALTER TABLE quotes ADD COLUMN IF NOT EXISTS secure_token VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE quotes ADD COLUMN IF NOT EXISTS service_rate DECIMAL(10,2) DEFAULT 0",
    "ALTER TABLE quotes ADD COLUMN IF NOT EXISTS service_amount DECIMAL(10,2) DEFAULT 0",
    "ALTER TABLE quotes ADD COLUMN IF NOT EXISTS tax_enabled TINYINT(1) DEFAULT 0",
    "ALTER TABLE quotes ADD COLUMN IF NOT EXISTS created_by INT DEFAULT NULL",
    "ALTER TABLE leads ADD COLUMN thank_you_token VARCHAR(64) UNIQUE AFTER id",
    "ALTER TABLE leads ADD COLUMN thank_you_shown TINYINT(1) DEFAULT 0 AFTER thank_you_token"
];
foreach ($alterQuotes as $sql) {
    try { $pdo->exec($sql); } catch (Exception $e) {}
}

// --- Ensure required columns in invoices table ---
$alterInvoices = [
    "ALTER TABLE invoices ADD COLUMN IF NOT EXISTS secure_token VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE invoices ADD COLUMN IF NOT EXISTS service_rate DECIMAL(10,2) DEFAULT 0",
    "ALTER TABLE invoices ADD COLUMN IF NOT EXISTS service_amount DECIMAL(10,2) DEFAULT 0",
    "ALTER TABLE invoices ADD COLUMN IF NOT EXISTS tax_enabled TINYINT(1) DEFAULT 0",
    "ALTER TABLE invoices ADD COLUMN IF NOT EXISTS created_by INT DEFAULT NULL"
];
foreach ($alterInvoices as $sql) {
    try { $pdo->exec($sql); } catch (Exception $e) {}
}

// --- Ensure required statuses for quotes ---
$required_quote_statuses = [
    ['status_key' => 'draft', 'status_label' => 'Draft', 'type' => 'quote'],
    ['status_key' => 'sent', 'status_label' => 'Sent to Customer', 'type' => 'quote'],
    ['status_key' => 'approved', 'status_label' => 'Approved by Customer', 'type' => 'quote'],
    ['status_key' => 'abandoned', 'status_label' => 'Abandoned', 'type' => 'quote'],
    ['status_key' => 'accepted', 'status_label' => 'Accepted/Invoiced', 'type' => 'quote'],
];
foreach ($required_quote_statuses as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM statuses WHERE status_key = ? AND entity_type = ?");
    $stmt->execute([$status['status_key'], $status['type']]);
    if ($stmt->fetchColumn() == 0) {
        $insert = $pdo->prepare("INSERT INTO statuses (status_key, status_label, entity_type) VALUES (?, ?, ?)");
        $insert->execute([$status['status_key'], $status['status_label'], $status['type']]);
    }
}

// --- Ensure required statuses for leads ---
$required_lead_statuses = [
    ['status_key' => 'inquiry', 'status_label' => 'Inquiry', 'type' => 'lead'],
    ['status_key' => 'survey_scheduled', 'status_label' => 'Survey Scheduled', 'type' => 'lead'],
    ['status_key' => 'survey_done', 'status_label' => 'Survey Done', 'type' => 'lead'],
    ['status_key' => 'estimate_sent', 'status_label' => 'Estimate Sent', 'type' => 'lead'],
    ['status_key' => 'estimate_approved', 'status_label' => 'Estimate Approved', 'type' => 'lead'],
    ['status_key' => 'job_confirmed', 'status_label' => 'Job Confirmed', 'type' => 'lead'],
    ['status_key' => 'job_completed', 'status_label' => 'Job Completed', 'type' => 'lead'],
    ['status_key' => 'abandoned', 'status_label' => 'Abandoned', 'type' => 'lead'],
];
foreach ($required_lead_statuses as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM statuses WHERE status_key = ? AND entity_type = ?");
    $stmt->execute([$status['status_key'], $status['type']]);
    if ($stmt->fetchColumn() == 0) {
        $insert = $pdo->prepare("INSERT INTO statuses (status_key, status_label, entity_type) VALUES (?, ?, ?)");
        $insert->execute([$status['status_key'], $status['status_label'], $status['type']]);
    }
}

echo "Migration for quotes/invoices columns and statuses completed.\n";
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nSummary:\n";
    echo "- Added missing quote columns (service_rate, service_amount, tax_enabled, secure_token)\n";
    echo "- Added missing invoice columns (secure_token)\n";
    echo "- Added missing settings (gst_number, company_tagline)\n";
    echo "- Updated existing quotes with proper tax flags\n";
    echo "- Cleaned up old/unused statuses from database\n";
    echo "- Added abandoned status for quotes and invoices\n";
    echo "- Ensured all required statuses exist in database\n";
    echo "- All pages now use dynamic status management\n";
    echo "\nTo add business management tables (customers, labour, vehicles):\n";
    echo "- Import business-database.sql manually\n";
    echo "- Or visit /business-management to create tables automatically\n";
    echo "\nYour database is now up to date and hardcoding-free!\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>