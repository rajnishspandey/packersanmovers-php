<?php
// Create admin user script - run this once to create admin user
require_once 'config.php';

// Show form if no POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<form method="POST" style="max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ccc;">';
    echo '<h3>Create Admin User</h3>';
    echo '<p><label>Username: <input type="text" name="username" value="admin" required style="width: 100%; padding: 5px;"></label></p>';
    echo '<p><label>Email: <input type="email" name="email" value="admin@' . ($_SERVER['HTTP_HOST'] ?? 'packersanmovers.com') . '" required style="width: 100%; padding: 5px;"></label></p>';
    echo '<p><label>Password: <input type="password" name="password" value="admin123" required style="width: 100%; padding: 5px;"></label></p>';
    echo '<p><button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none;">Create Admin User</button></p>';
    echo '</form>';
    exit;
}

try {
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$_POST['username'] ?? 'admin']);
    $admin_exists = $stmt->fetchColumn();
    
    if ($admin_exists > 0) {
        echo "✅ Admin user already exists!<br>";
        echo "Username: " . htmlspecialchars($_POST['username'] ?? 'admin') . "<br>";
        echo "Try logging in at: <a href='/pmlogin'>/pmlogin</a><br>";
        echo "<br><strong>⚠️ IMPORTANT: Delete this file (/create-admin.php) for security!</strong>";
    } else {
        // Create admin user with configurable credentials
        $username = $_POST['username'] ?? 'admin';
        $email = $_POST['email'] ?? 'admin@' . ($_SERVER['HTTP_HOST'] ?? 'packersanmovers.com');
        $password = $_POST['password'] ?? 'admin123';
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, is_admin) VALUES (?, ?, ?, 'admin', 1)");
        $result = $stmt->execute([$username, $email, $password_hash]);
        
        if ($result) {
            echo "✅ Admin user created successfully!<br>";
            echo "Username: " . htmlspecialchars($username) . "<br>";
            echo "Password: " . htmlspecialchars($password) . "<br>";
            echo "<br>You can now login at: <a href='/pmlogin'>/pmlogin</a>";
            echo "<br><br><strong>⚠️ IMPORTANT: Delete this file (/create-admin.php) for security!</strong>";
        } else {
            echo "❌ Failed to create admin user";
        }
    }
    
    // Show all users in database
    echo "<br><br><strong>Current users in database:</strong><br>";
    $stmt = $pdo->query("SELECT id, username, email, role, is_admin FROM users");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "No users found in database";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Is Admin</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . ($user['is_admin'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage();
    echo "<br><br>Make sure your database connection is working and the users table exists.";
}
?>