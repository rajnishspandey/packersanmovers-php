<?php
require_once 'config.php';
require_once 'includes/mail.php';

$page_title = 'Get a Free Quote';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $move_from = sanitize_input($_POST['move_from']);
    $move_to = sanitize_input($_POST['move_to']);
    $move_date = $_POST['move_date'];
    $home_size = sanitize_input($_POST['home_size']);
    $additional_services = isset($_POST['additional_services']) ? implode(', ', $_POST['additional_services']) : '';
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    // Validate required fields
    if (!empty($name) && !empty($email) && !empty($phone) && !empty($move_from) && 
        !empty($move_to) && !empty($move_date) && !empty($home_size)) {
        
        // Backend validation: Prevent past dates for public submissions
        if (strtotime($move_date) < strtotime(date('Y-m-d'))) {
            $_SESSION['message'] = 'Please select a move date that is today or in the future.';
        } else {
        
        try {
            // Generate thank you token
            $thank_you_token = bin2hex(random_bytes(16));
            
            // Try to insert with thank you token columns
            try {
                $stmt = $pdo->prepare("INSERT INTO leads (name, email, phone, move_from, move_to, move_date, home_size, additional_services, notes, status, thank_you_token, thank_you_shown) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'inquiry', ?, 0)");
                $stmt->execute([$name, $email, $phone, $move_from, $move_to, $move_date, $home_size, $additional_services, $notes, $thank_you_token]);
            } catch (Exception $e) {
                // Fallback for databases without thank you columns
                $stmt = $pdo->prepare("INSERT INTO leads (name, email, phone, move_from, move_to, move_date, home_size, additional_services, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'inquiry')");
                $stmt->execute([$name, $email, $phone, $move_from, $move_to, $move_date, $home_size, $additional_services, $notes]);
                $lead_id = $pdo->lastInsertId();
                $thank_you_token = 'lead_' . $lead_id;
            }
            
            // Redirect immediately for fast response
            header('Location: /thank-you?source=quote&token=' . $thank_you_token);
            exit;
            
        } catch (Exception $e) {
            $_SESSION['message'] = 'There was an error submitting your request. Please try again later.';
        }
        } // End of date validation
    } else {
        $_SESSION['message'] = 'Please fill in all required fields.';
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="text-center mb-5">Get a Free Moving Quote</h1>
            
            <div class="card shadow">
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="move_from" class="form-label">Moving From *</label>
                                <input type="text" class="form-control" id="move_from" name="move_from" placeholder="Current address" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="move_to" class="form-label">Moving To *</label>
                                <input type="text" class="form-control" id="move_to" name="move_to" placeholder="Destination address" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="move_date" class="form-label">Preferred Move Date *</label>
                                <input type="date" class="form-control" id="move_date" name="move_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="home_size" class="form-label">Property Size *</label>
                                <select class="form-select" id="home_size" name="home_size" required>
                                    <option value="">Select property size</option>
                                    <option value="1 BHK">1 BHK</option>
                                    <option value="2 BHK">2 BHK</option>
                                    <option value="3 BHK">3 BHK</option>
                                    <option value="4+ BHK">4+ BHK</option>
                                    <option value="Villa/Bungalow">Villa/Bungalow</option>
                                    <option value="Office">Office</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Additional Services</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="additional_services[]" value="Packing" id="packing">
                                        <label class="form-check-label" for="packing">Packing Services</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="additional_services[]" value="Storage" id="storage">
                                        <label class="form-check-label" for="storage">Storage Services</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="additional_services[]" value="Insurance" id="insurance">
                                        <label class="form-check-label" for="insurance">Insurance Coverage</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="additional_services[]" value="Unpacking" id="unpacking">
                                        <label class="form-check-label" for="unpacking">Unpacking Services</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="additional_services[]" value="Cleaning" id="cleaning">
                                        <label class="form-check-label" for="cleaning">Cleaning Services</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="additional_services[]" value="Assembly" id="assembly">
                                        <label class="form-check-label" for="assembly">Furniture Assembly</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any special requirements or additional information"></textarea>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg" id="quoteSubmitBtn">Get My Free Quote</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = document.getElementById('quoteSubmitBtn');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    });
});
</script>

<?php include 'includes/footer.php'; ?>