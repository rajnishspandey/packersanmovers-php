<?php
require_once 'config.php';
require_permission('leads', 'read');

$page_title = 'Business Management';

// Check if business tables exist, create if not
try {
    $pdo->query("SELECT 1 FROM customers LIMIT 1");
} catch (Exception $e) {
    // Tables don't exist, create them
    $business_sql = file_get_contents('business-database.sql');
    if ($business_sql) {
        $statements = explode(';', $business_sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (Exception $ex) {
                    // Ignore errors for existing tables
                }
            }
        }
    }
}

// Function to sync leads to customers
function sync_leads_to_customers() {
    global $pdo;
    
    // Get leads that don't have corresponding customers
    $stmt = $pdo->query("SELECT DISTINCT name, email, phone, move_from as address, created_at 
                         FROM leads 
                         WHERE name NOT IN (SELECT name FROM customers WHERE name IS NOT NULL)
                         ORDER BY created_at DESC");
    $leads_to_sync = $stmt->fetchAll();
    
    foreach ($leads_to_sync as $lead) {
        try {
            $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address, customer_type, created_at) 
                                  VALUES (?, ?, ?, ?, 'individual', ?)");
            $stmt->execute([$lead['name'], $lead['email'], $lead['phone'], $lead['address'], $lead['created_at']]);
        } catch (Exception $e) {
            // Skip duplicates
        }
    }
    
    // Update total_moves count for customers
    $pdo->exec("UPDATE customers c SET total_moves = (
                    SELECT COUNT(*) FROM leads l WHERE l.name = c.name AND l.phone = c.phone
                ) WHERE total_moves = 0");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_customer':
                require_permission('leads', 'create');
                $name = sanitize_input($_POST['name']);
                $phone = sanitize_input($_POST['phone']);
                $email = sanitize_input($_POST['email']);
                $address = sanitize_input($_POST['address']);
                $city = sanitize_input($_POST['city']);
                $customer_type = sanitize_input($_POST['customer_type']);
                
                $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address, city, customer_type) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $phone, $email, $address, $city, $customer_type]);
                $_SESSION['message'] = 'Customer added successfully!';
                break;
                
            case 'add_labour':
                require_permission('leads', 'create');
                $name = sanitize_input($_POST['name']);
                $phone = sanitize_input($_POST['phone']);
                $specialization = sanitize_input($_POST['specialization']);
                $daily_rate = $_POST['daily_rate'];
                $experience_years = $_POST['experience_years'];
                
                $stmt = $pdo->prepare("INSERT INTO labours (name, phone, specialization, daily_rate, experience_years) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $phone, $specialization, $daily_rate, $experience_years]);
                $_SESSION['message'] = 'Labour added successfully!';
                redirect('/business-management#labours');
                break;
                
            case 'add_vehicle':
                require_permission('leads', 'create');
                $vehicle_number = sanitize_input($_POST['vehicle_number']);
                $vehicle_type = sanitize_input($_POST['vehicle_type']);
                $capacity_tons = $_POST['capacity_tons'];
                $owner_type = sanitize_input($_POST['owner_type']);
                $daily_rate = $_POST['daily_rate'];
                
                $stmt = $pdo->prepare("INSERT INTO vehicles (vehicle_number, vehicle_type, capacity_tons, owner_type, daily_rate) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$vehicle_number, $vehicle_type, $capacity_tons, $owner_type, $daily_rate]);
                $_SESSION['message'] = 'Vehicle added successfully!';
                redirect('/business-management#vehicles');
                break;
                
            case 'sync_leads':
                require_permission('leads', 'update');
                sync_leads_to_customers();
                $_SESSION['message'] = 'Existing leads synced to customer database!';
                break;
                
            case 'edit_customer':
                require_admin();
                $id = $_POST['customer_id'];
                $name = sanitize_input($_POST['name']);
                $phone = sanitize_input($_POST['phone']);
                $email = sanitize_input($_POST['email']) ?: null;
                $city = sanitize_input($_POST['city']) ?: null;
                $status = sanitize_input($_POST['status']);
                
                $stmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, email = ?, city = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $email, $city, $status, $id]);
                $_SESSION['message'] = 'Customer updated successfully!';
                break;
                
            case 'delete_customer':
                require_admin();
                $id = $_POST['customer_id'];
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['message'] = 'Customer deleted successfully!';
                break;
                
            case 'edit_labour':
                require_admin();
                $id = $_POST['labour_id'];
                $name = sanitize_input($_POST['name']);
                $phone = sanitize_input($_POST['phone']);
                $specialization = sanitize_input($_POST['specialization']);
                $daily_rate = $_POST['daily_rate'] ?: 0;
                $availability = sanitize_input($_POST['availability']);
                
                $stmt = $pdo->prepare("UPDATE labours SET name = ?, phone = ?, specialization = ?, daily_rate = ?, availability = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $specialization, $daily_rate, $availability, $id]);
                $_SESSION['message'] = 'Labour updated successfully!';
                redirect('/business-management#labours');
                break;
                
            case 'delete_labour':
                require_admin();
                $id = $_POST['labour_id'];
                $stmt = $pdo->prepare("DELETE FROM labours WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['message'] = 'Labour deleted successfully!';
                redirect('/business-management#labours');
                break;
                
            case 'edit_vehicle':
                require_admin();
                $id = $_POST['vehicle_id'];
                $vehicle_number = sanitize_input($_POST['vehicle_number']);
                $vehicle_type = sanitize_input($_POST['vehicle_type']);
                $capacity_tons = $_POST['capacity_tons'] ?: null;
                $daily_rate = $_POST['daily_rate'] ?: 0;
                $status = sanitize_input($_POST['status']);
                
                $stmt = $pdo->prepare("UPDATE vehicles SET vehicle_number = ?, vehicle_type = ?, capacity_tons = ?, daily_rate = ?, status = ? WHERE id = ?");
                $stmt->execute([$vehicle_number, $vehicle_type, $capacity_tons, $daily_rate, $status, $id]);
                $_SESSION['message'] = 'Vehicle updated successfully!';
                redirect('/business-management#vehicles');
                break;
                
            case 'delete_vehicle':
                require_admin();
                $id = $_POST['vehicle_id'];
                $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['message'] = 'Vehicle deleted successfully!';
                redirect('/business-management#vehicles');
                break;
                
            case 'add_vendor':
                require_permission('leads', 'create');
                try {
                    $company_name = sanitize_input($_POST['company_name']);
                    $contact_person = sanitize_input($_POST['contact_person']);
                    $phone = sanitize_input($_POST['phone']);
                    $email = sanitize_input($_POST['email']) ?: null;
                    $service_type = sanitize_input($_POST['service_type']);
                    $city = sanitize_input($_POST['city']) ?: null;
                    
                    // Handle file upload
                    $visiting_card = null;
                    $visiting_card_name = null;
                    $visiting_card_type = null;
                    if (isset($_FILES['visiting_card']) && $_FILES['visiting_card']['error'] == 0) {
                        $file_ext = strtolower(pathinfo($_FILES['visiting_card']['name'], PATHINFO_EXTENSION));
                        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
                        
                        if (in_array($file_ext, $allowed_ext)) {
                            $visiting_card = file_get_contents($_FILES['visiting_card']['tmp_name']);
                            $visiting_card_name = $_FILES['visiting_card']['name'];
                            $visiting_card_type = $_FILES['visiting_card']['type'];
                        }
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO vendors (company_name, contact_person, phone, email, service_type, city, visiting_card, visiting_card_name, visiting_card_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$company_name, $contact_person, $phone, $email, $service_type, $city, $visiting_card, $visiting_card_name, $visiting_card_type]);
                    $_SESSION['message'] = 'Vendor added successfully!';
                } catch (Exception $e) {
                    $_SESSION['message'] = 'Error: Vendors table not found. Please run vendors-suppliers.sql first.';
                }
                redirect('/business-management#vendors');
                break;
                
            case 'add_supplier':
                require_permission('leads', 'create');
                try {
                    $company_name = sanitize_input($_POST['company_name']);
                    $contact_person = sanitize_input($_POST['contact_person']);
                    $phone = sanitize_input($_POST['phone']);
                    $email = sanitize_input($_POST['email']) ?: null;
                    $product_type = sanitize_input($_POST['product_type']);
                    $city = sanitize_input($_POST['city']) ?: null;
                    
                    // Handle file upload
                    $visiting_card = null;
                    $visiting_card_name = null;
                    $visiting_card_type = null;
                    if (isset($_FILES['visiting_card']) && $_FILES['visiting_card']['error'] == 0) {
                        $file_ext = strtolower(pathinfo($_FILES['visiting_card']['name'], PATHINFO_EXTENSION));
                        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
                        
                        if (in_array($file_ext, $allowed_ext)) {
                            $visiting_card = file_get_contents($_FILES['visiting_card']['tmp_name']);
                            $visiting_card_name = $_FILES['visiting_card']['name'];
                            $visiting_card_type = $_FILES['visiting_card']['type'];
                        }
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO suppliers (company_name, contact_person, phone, email, product_type, city, visiting_card, visiting_card_name, visiting_card_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$company_name, $contact_person, $phone, $email, $product_type, $city, $visiting_card, $visiting_card_name, $visiting_card_type]);
                    $_SESSION['message'] = 'Supplier added successfully!';
                } catch (Exception $e) {
                    $_SESSION['message'] = 'Error: Suppliers table not found. Please run vendors-suppliers.sql first.';
                }
                redirect('/business-management#suppliers');
                break;
                
            case 'delete_vendor':
                require_admin();
                $id = $_POST['vendor_id'];
                $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['message'] = 'Vendor deleted successfully!';
                redirect('/business-management#vendors');
                break;
                
            case 'delete_supplier':
                require_admin();
                $id = $_POST['supplier_id'];
                $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['message'] = 'Supplier deleted successfully!';
                redirect('/business-management#suppliers');
                break;
                
            case 'edit_vendor':
                require_permission('leads', 'update');
                try {
                    $id = $_POST['vendor_id'];
                    $company_name = sanitize_input($_POST['company_name']);
                    $contact_person = sanitize_input($_POST['contact_person']) ?: null;
                    $phone = sanitize_input($_POST['phone']);
                    $email = sanitize_input($_POST['email']) ?: null;
                    $service_type = sanitize_input($_POST['service_type']);
                    $city = sanitize_input($_POST['city']) ?: null;
                    
                    // Handle file upload if new file provided
                    if (isset($_FILES['visiting_card']) && $_FILES['visiting_card']['error'] == 0) {
                        $file_ext = strtolower(pathinfo($_FILES['visiting_card']['name'], PATHINFO_EXTENSION));
                        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
                        
                        if (in_array($file_ext, $allowed_ext)) {
                            $visiting_card = file_get_contents($_FILES['visiting_card']['tmp_name']);
                            $visiting_card_name = $_FILES['visiting_card']['name'];
                            $visiting_card_type = $_FILES['visiting_card']['type'];
                            
                            $stmt = $pdo->prepare("UPDATE vendors SET company_name = ?, contact_person = ?, phone = ?, email = ?, service_type = ?, city = ?, visiting_card = ?, visiting_card_name = ?, visiting_card_type = ? WHERE id = ?");
                            $stmt->execute([$company_name, $contact_person, $phone, $email, $service_type, $city, $visiting_card, $visiting_card_name, $visiting_card_type, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE vendors SET company_name = ?, contact_person = ?, phone = ?, email = ?, service_type = ?, city = ? WHERE id = ?");
                            $stmt->execute([$company_name, $contact_person, $phone, $email, $service_type, $city, $id]);
                        }
                    } else {
                        $stmt = $pdo->prepare("UPDATE vendors SET company_name = ?, contact_person = ?, phone = ?, email = ?, service_type = ?, city = ? WHERE id = ?");
                        $stmt->execute([$company_name, $contact_person, $phone, $email, $service_type, $city, $id]);
                    }
                    
                    $_SESSION['message'] = 'Vendor updated successfully!';
                } catch (Exception $e) {
                    $_SESSION['message'] = 'Error updating vendor.';
                }
                redirect('/business-management#vendors');
                break;
                
            case 'edit_supplier':
                require_permission('leads', 'update');
                try {
                    $id = $_POST['supplier_id'];
                    $company_name = sanitize_input($_POST['company_name']);
                    $contact_person = sanitize_input($_POST['contact_person']) ?: null;
                    $phone = sanitize_input($_POST['phone']);
                    $email = sanitize_input($_POST['email']) ?: null;
                    $product_type = sanitize_input($_POST['product_type']);
                    $city = sanitize_input($_POST['city']) ?: null;
                    
                    // Handle file upload if new file provided
                    if (isset($_FILES['visiting_card']) && $_FILES['visiting_card']['error'] == 0) {
                        $file_ext = strtolower(pathinfo($_FILES['visiting_card']['name'], PATHINFO_EXTENSION));
                        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
                        
                        if (in_array($file_ext, $allowed_ext)) {
                            $visiting_card = file_get_contents($_FILES['visiting_card']['tmp_name']);
                            $visiting_card_name = $_FILES['visiting_card']['name'];
                            $visiting_card_type = $_FILES['visiting_card']['type'];
                            
                            $stmt = $pdo->prepare("UPDATE suppliers SET company_name = ?, contact_person = ?, phone = ?, email = ?, product_type = ?, city = ?, visiting_card = ?, visiting_card_name = ?, visiting_card_type = ? WHERE id = ?");
                            $stmt->execute([$company_name, $contact_person, $phone, $email, $product_type, $city, $visiting_card, $visiting_card_name, $visiting_card_type, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE suppliers SET company_name = ?, contact_person = ?, phone = ?, email = ?, product_type = ?, city = ? WHERE id = ?");
                            $stmt->execute([$company_name, $contact_person, $phone, $email, $product_type, $city, $id]);
                        }
                    } else {
                        $stmt = $pdo->prepare("UPDATE suppliers SET company_name = ?, contact_person = ?, phone = ?, email = ?, product_type = ?, city = ? WHERE id = ?");
                        $stmt->execute([$company_name, $contact_person, $phone, $email, $product_type, $city, $id]);
                    }
                    
                    $_SESSION['message'] = 'Supplier updated successfully!';
                } catch (Exception $e) {
                    $_SESSION['message'] = 'Error updating supplier.';
                }
                redirect('/business-management#suppliers');
                break;
        }
        redirect('/business-management');
    }
}

// Auto-sync leads to customers on page load
try {
    sync_leads_to_customers();
} catch (Exception $e) {
    // Ignore sync errors
}

// Get data safely with lead history
try {
    $customers = $pdo->query("SELECT c.*, 
                             (SELECT COUNT(*) FROM leads l WHERE l.name = c.name AND l.phone = c.phone) as actual_moves,
                             (SELECT MAX(l.created_at) FROM leads l WHERE l.name = c.name AND l.phone = c.phone) as last_move_date
                             FROM customers c 
                             ORDER BY c.created_at DESC LIMIT 50")->fetchAll();
} catch (Exception $e) {
    $customers = [];
}

try {
    $labours = $pdo->query("SELECT * FROM labours ORDER BY created_at DESC LIMIT 50")->fetchAll();
} catch (Exception $e) {
    $labours = [];
}

try {
    $vehicles = $pdo->query("SELECT * FROM vehicles ORDER BY created_at DESC LIMIT 50")->fetchAll();
} catch (Exception $e) {
    $vehicles = [];
}

try {
    $vendors = $pdo->query("SELECT * FROM vendors ORDER BY created_at DESC LIMIT 50")->fetchAll();
} catch (Exception $e) {
    $vendors = [];
}

try {
    $suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY created_at DESC LIMIT 50")->fetchAll();
} catch (Exception $e) {
    $suppliers = [];
}

include 'includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-building"></i> Business Management</h1>
        <div>
            <a href="/leads" class="btn btn-outline-primary">Back to Leads</a>
        </div>
    </div>
    
    <?php if (empty($customers) && empty($labours) && empty($vehicles)): ?>
    <div class="alert alert-info">
        <h5><i class="fas fa-info-circle"></i> Setup Required</h5>
        <p>Business management tables are being created. Please refresh the page or import <code>business-database.sql</code> manually.</p>
        <a href="/business-management" class="btn btn-primary">Refresh Page</a>
    </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs" id="businessTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="customers-tab" data-bs-toggle="tab" data-bs-target="#customers" type="button">
                <i class="fas fa-users"></i> Customers
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="labours-tab" data-bs-toggle="tab" data-bs-target="#labours" type="button">
                <i class="fas fa-hard-hat"></i> Labour
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles" type="button">
                <i class="fas fa-truck"></i> Vehicles
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="vendors-tab" data-bs-toggle="tab" data-bs-target="#vendors" type="button">
                <i class="fas fa-handshake"></i> Vendors
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="suppliers-tab" data-bs-toggle="tab" data-bs-target="#suppliers" type="button">
                <i class="fas fa-boxes"></i> Suppliers
            </button>
        </li>
    </ul>

    <div class="tab-content" id="businessTabContent">
        <!-- Customers Tab -->
        <div class="tab-pane fade show active" id="customers" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Customer Database</h5>
                    <div>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="sync_leads">
                            <button type="submit" class="btn btn-outline-info btn-sm me-2">
                                <i class="fas fa-sync"></i> Sync Leads
                            </button>
                        </form>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                            <i class="fas fa-plus"></i> Add Customer
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>City</th>
                                    <th>Type</th>
                                    <th>Moves</th>
                                    <th>Last Move</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($customer['city'] ?? 'N/A'); ?></td>
                                    <td><span class="badge bg-info"><?php echo ucfirst($customer['customer_type']); ?></span></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $customer['actual_moves']; ?></span>
                                        <?php if ($customer['actual_moves'] != $customer['total_moves']): ?>
                                        <small class="text-muted d-block">DB: <?php echo $customer['total_moves']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($customer['last_move_date']): ?>
                                        <small><?php echo date('M d, Y', strtotime($customer['last_move_date'])); ?></small>
                                        <?php else: ?>
                                        <small class="text-muted">No moves</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($customer['rating'] > 0): ?>
                                        <span class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $customer['rating'] ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </span>
                                        <?php else: ?>
                                        <small class="text-muted">No rating</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-success"><?php echo ucfirst($customer['status'] ?? 'active'); ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editCustomerModal<?php echo $customer['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this customer?')">
                                            <input type="hidden" name="action" value="delete_customer">
                                            <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Labour Tab -->
        <div class="tab-pane fade" id="labours" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Labour Database</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLabourModal">
                        <i class="fas fa-plus"></i> Add Labour
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Specialization</th>
                                    <th>Experience</th>
                                    <th>Daily Rate</th>
                                    <th>Rating</th>
                                    <th>Availability</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($labours as $labour): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($labour['name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($labour['phone'] ?? ''); ?></td>
                                    <td><span class="badge bg-primary"><?php echo ucfirst($labour['specialization']); ?></span></td>
                                    <td><?php echo $labour['experience_years']; ?> years</td>
                                    <td>Rs <?php echo number_format($labour['daily_rate'], 0); ?></td>
                                    <td>
                                        <?php if ($labour['rating'] > 0): ?>
                                        <span class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $labour['rating'] ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </span>
                                        <?php else: ?>
                                        <small class="text-muted">No rating</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $labour['availability'] == 'available' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($labour['availability']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editLabourModal<?php echo $labour['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this labour?')">
                                            <input type="hidden" name="action" value="delete_labour">
                                            <input type="hidden" name="labour_id" value="<?php echo $labour['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicles Tab -->
        <div class="tab-pane fade" id="vehicles" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Vehicle Database</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                        <i class="fas fa-plus"></i> Add Vehicle
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Vehicle Number</th>
                                    <th>Type</th>
                                    <th>Capacity</th>
                                    <th>Owner Type</th>
                                    <th>Daily Rate</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vehicles as $vehicle): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($vehicle['vehicle_number'] ?? ''); ?></strong></td>
                                    <td><span class="badge bg-info"><?php echo ucfirst($vehicle['vehicle_type']); ?></span></td>
                                    <td><?php echo $vehicle['capacity_tons']; ?> tons</td>
                                    <td><span class="badge bg-secondary"><?php echo ucfirst($vehicle['owner_type']); ?></span></td>
                                    <td>Rs <?php echo number_format($vehicle['daily_rate'], 0); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $vehicle['status'] == 'available' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($vehicle['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editVehicleModal<?php echo $vehicle['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this vehicle?')">
                                            <input type="hidden" name="action" value="delete_vehicle">
                                            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendors Tab -->
        <div class="tab-pane fade" id="vendors" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Vendor Database</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addVendorModal">
                        <i class="fas fa-plus"></i> Add Vendor
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Contact Person</th>
                                    <th>Phone</th>
                                    <th>Service Type</th>
                                    <th>City</th>
                                    <th>Visiting Card</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($vendors): ?>
                                <?php foreach ($vendors as $vendor): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vendor['company_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($vendor['contact_person'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vendor['phone']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($vendor['service_type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($vendor['city'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (!empty($vendor['visiting_card'])): ?>
                                        <button class="btn btn-sm btn-outline-info" onclick="showCard('<?php echo base64_encode($vendor['visiting_card']); ?>', '<?php echo $vendor['visiting_card_type']; ?>', '<?php echo htmlspecialchars($vendor['visiting_card_name']); ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php else: ?>
                                        <small class="text-muted">No card</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($vendor['rating'] > 0): ?>
                                        <span class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $vendor['rating'] ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </span>
                                        <?php else: ?>
                                        <small class="text-muted">No rating</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-<?php echo $vendor['status'] == 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($vendor['status']); ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editVendorModal<?php echo $vendor['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this vendor?')">
                                            <input type="hidden" name="action" value="delete_vendor">
                                            <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No vendors added yet. Click "Add Vendor" to get started.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suppliers Tab -->
        <div class="tab-pane fade" id="suppliers" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Supplier Database</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                        <i class="fas fa-plus"></i> Add Supplier
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Contact Person</th>
                                    <th>Phone</th>
                                    <th>Product Type</th>
                                    <th>City</th>
                                    <th>Visiting Card</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($suppliers): ?>
                                <?php foreach ($suppliers as $supplier): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($supplier['company_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['contact_person'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($supplier['product_type'] ?? ''); ?></span></td>
                                    <td><?php echo htmlspecialchars($supplier['city'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (!empty($supplier['visiting_card'])): ?>
                                        <button class="btn btn-sm btn-outline-info" onclick="showCard('<?php echo base64_encode($supplier['visiting_card']); ?>', '<?php echo $supplier['visiting_card_type']; ?>', '<?php echo htmlspecialchars($supplier['visiting_card_name']); ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php else: ?>
                                        <small class="text-muted">No card</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($supplier['rating'] > 0): ?>
                                        <span class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $supplier['rating'] ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </span>
                                        <?php else: ?>
                                        <small class="text-muted">No rating</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-<?php echo $supplier['status'] == 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($supplier['status']); ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?php echo $supplier['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this supplier?')">
                                            <input type="hidden" name="action" value="delete_supplier">
                                            <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No suppliers added yet. Click "Add Supplier" to get started.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_customer">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Customer Type</label>
                        <select name="customer_type" class="form-select">
                            <option value="individual">Individual</option>
                            <option value="corporate">Corporate</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Labour Modal -->
<div class="modal fade" id="addLabourModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Labour</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_labour">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Specialization</label>
                        <select name="specialization" class="form-select">
                            <option value="general">General</option>
                            <option value="packing">Packing</option>
                            <option value="loading">Loading</option>
                            <option value="driving">Driving</option>
                            <option value="supervisor">Supervisor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Daily Rate (Rs)</label>
                        <input type="number" name="daily_rate" class="form-control" min="0" step="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Experience (Years)</label>
                        <input type="number" name="experience_years" class="form-control" min="0" max="50">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Labour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Vehicle Modal -->
<div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_vehicle">
                    <div class="mb-3">
                        <label class="form-label">Vehicle Number *</label>
                        <input type="text" name="vehicle_number" class="form-control" placeholder="DL01AB1234" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vehicle Type</label>
                        <select name="vehicle_type" class="form-select">
                            <option value="mini_truck">Mini Truck</option>
                            <option value="tempo">Tempo</option>
                            <option value="truck">Truck</option>
                            <option value="container">Container</option>
                            <option value="van">Van</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity (Tons)</label>
                        <input type="number" name="capacity_tons" class="form-control" min="0" step="0.5">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Owner Type</label>
                        <select name="owner_type" class="form-select">
                            <option value="owned">Owned</option>
                            <option value="rented">Rented</option>
                            <option value="partner">Partner</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Daily Rate (Rs)</label>
                        <input type="number" name="daily_rate" class="form-control" min="0" step="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Vendor Modal -->
<div class="modal fade" id="addVendorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_vendor">
                    <div class="mb-3">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person *</label>
                        <input type="text" name="contact_person" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Service Type *</label>
                        <input type="text" name="service_type" class="form-control" placeholder="e.g., Transport, Storage, Insurance" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Visiting Card</label>
                        <input type="file" name="visiting_card" class="form-control" accept="image/*,.pdf">
                        <small class="text-muted">Upload visiting card (Image or PDF)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_supplier">
                    <div class="mb-3">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person *</label>
                        <input type="text" name="contact_person" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Type *</label>
                        <input type="text" name="product_type" class="form-control" placeholder="e.g., Boxes, Bubble Wrap, Tape" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Visiting Card</label>
                        <input type="file" name="visiting_card" class="form-control" accept="image/*,.pdf">
                        <small class="text-muted">Upload visiting card (Image or PDF)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Customer Modals -->
<?php foreach ($customers as $customer): ?>
<div class="modal fade" id="editCustomerModal<?php echo $customer['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_customer">
                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo ($customer['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($customer['status'] ?? 'active') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="blacklisted" <?php echo ($customer['status'] ?? 'active') == 'blacklisted' ? 'selected' : ''; ?>>Blacklisted</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Edit Labour Modals -->
<?php foreach ($labours as $labour): ?>
<div class="modal fade" id="editLabourModal<?php echo $labour['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Labour</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_labour">
                    <input type="hidden" name="labour_id" value="<?php echo $labour['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($labour['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($labour['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Specialization</label>
                        <select name="specialization" class="form-select">
                            <option value="general" <?php echo ($labour['specialization'] ?? 'general') == 'general' ? 'selected' : ''; ?>>General</option>
                            <option value="packing" <?php echo ($labour['specialization'] ?? 'general') == 'packing' ? 'selected' : ''; ?>>Packing</option>
                            <option value="loading" <?php echo ($labour['specialization'] ?? 'general') == 'loading' ? 'selected' : ''; ?>>Loading</option>
                            <option value="driving" <?php echo ($labour['specialization'] ?? 'general') == 'driving' ? 'selected' : ''; ?>>Driving</option>
                            <option value="supervisor" <?php echo ($labour['specialization'] ?? 'general') == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Daily Rate (Rs)</label>
                        <input type="number" name="daily_rate" class="form-control" value="<?php echo $labour['daily_rate'] ?? 0; ?>" min="0" step="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Availability</label>
                        <select name="availability" class="form-select">
                            <option value="available" <?php echo ($labour['availability'] ?? 'available') == 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="busy" <?php echo ($labour['availability'] ?? 'available') == 'busy' ? 'selected' : ''; ?>>Busy</option>
                            <option value="on_leave" <?php echo ($labour['availability'] ?? 'available') == 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                            <option value="inactive" <?php echo ($labour['availability'] ?? 'available') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Labour</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Edit Vehicle Modals -->
<?php foreach ($vehicles as $vehicle): ?>
<div class="modal fade" id="editVehicleModal<?php echo $vehicle['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_vehicle">
                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Vehicle Number *</label>
                        <input type="text" name="vehicle_number" class="form-control" value="<?php echo htmlspecialchars($vehicle['vehicle_number'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vehicle Type</label>
                        <select name="vehicle_type" class="form-select">
                            <option value="mini_truck" <?php echo ($vehicle['vehicle_type'] ?? 'mini_truck') == 'mini_truck' ? 'selected' : ''; ?>>Mini Truck</option>
                            <option value="tempo" <?php echo ($vehicle['vehicle_type'] ?? 'mini_truck') == 'tempo' ? 'selected' : ''; ?>>Tempo</option>
                            <option value="truck" <?php echo ($vehicle['vehicle_type'] ?? 'mini_truck') == 'truck' ? 'selected' : ''; ?>>Truck</option>
                            <option value="container" <?php echo ($vehicle['vehicle_type'] ?? 'mini_truck') == 'container' ? 'selected' : ''; ?>>Container</option>
                            <option value="van" <?php echo ($vehicle['vehicle_type'] ?? 'mini_truck') == 'van' ? 'selected' : ''; ?>>Van</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity (Tons)</label>
                        <input type="number" name="capacity_tons" class="form-control" value="<?php echo $vehicle['capacity_tons'] ?? ''; ?>" min="0" step="0.5">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Daily Rate (Rs)</label>
                        <input type="number" name="daily_rate" class="form-control" value="<?php echo $vehicle['daily_rate'] ?? 0; ?>" min="0" step="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="available" <?php echo ($vehicle['status'] ?? 'available') == 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="in_use" <?php echo ($vehicle['status'] ?? 'available') == 'in_use' ? 'selected' : ''; ?>>In Use</option>
                            <option value="maintenance" <?php echo ($vehicle['status'] ?? 'available') == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="inactive" <?php echo ($vehicle['status'] ?? 'available') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
// Activate correct tab based on URL hash
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash) {
        const tabId = hash.substring(1);
        const tabButton = document.getElementById(tabId + '-tab');
        const tabPane = document.getElementById(tabId);
        
        if (tabButton && tabPane) {
            // Remove active from all tabs
            document.querySelectorAll('.nav-link').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Activate target tab
            tabButton.classList.add('active');
            tabPane.classList.add('show', 'active');
        }
    }
});
</script>

<!-- Visiting Card Modal -->
<div class="modal fade" id="cardModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visiting Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="cardContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
function showCard(base64Data, mimeType, filename) {
    const cardContent = document.getElementById('cardContent');
    
    if (mimeType.includes('pdf')) {
        cardContent.innerHTML = `<embed src="data:${mimeType};base64,${base64Data}" type="application/pdf" width="100%" height="500px" />`;
    } else {
        cardContent.innerHTML = `<img src="data:${mimeType};base64,${base64Data}" class="img-fluid" alt="${filename}" />`;
    }
    
    new bootstrap.Modal(document.getElementById('cardModal')).show();
}
</script>

<!-- Edit Vendor Modals -->
<?php foreach ($vendors as $vendor): ?>
<div class="modal fade" id="editVendorModal<?php echo $vendor['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_vendor">
                    <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($vendor['company_name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($vendor['contact_person'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($vendor['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($vendor['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Service Type *</label>
                        <input type="text" name="service_type" class="form-control" value="<?php echo htmlspecialchars($vendor['service_type'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($vendor['city'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Update Visiting Card</label>
                        <input type="file" name="visiting_card" class="form-control" accept="image/*,.pdf">
                        <small class="text-muted">Leave empty to keep current card</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Edit Supplier Modals -->
<?php foreach ($suppliers as $supplier): ?>
<div class="modal fade" id="editSupplierModal<?php echo $supplier['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_supplier">
                    <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($supplier['company_name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($supplier['contact_person'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($supplier['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($supplier['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Type *</label>
                        <input type="text" name="product_type" class="form-control" value="<?php echo htmlspecialchars($supplier['product_type'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($supplier['city'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Update Visiting Card</label>
                        <input type="file" name="visiting_card" class="form-control" accept="image/*,.pdf">
                        <small class="text-muted">Leave empty to keep current card</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include 'includes/footer.php'; ?>