<?php
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';

echo "<h3>Database Connection Test</h3>";

try {
    // Test connection
    echo "✅ Database connected successfully!<br><br>";
    
    // Check if admins table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Admins table exists<br><br>";
    } else {
        echo "❌ Admins table NOT found<br><br>";
    }
    
    // Check admin user
    $stmt = $pdo->query("SELECT * FROM admins WHERE username = 'admin'");
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin user found:<br>";
        echo "Username: " . $admin['username'] . "<br>";
        echo "Email: " . $admin['email'] . "<br>";
        echo "Full Name: " . $admin['full_name'] . "<br>";
        echo "Status: " . $admin['status'] . "<br>";
        echo "Password Hash: " . substr($admin['password'], 0, 20) . "...<br><br>";
        
        // Test password verification
        if (password_verify('admin123', $admin['password'])) {
            echo "✅ Password 'admin123' is CORRECT<br>";
        } else {
            echo "❌ Password 'admin123' is WRONG<br>";
            echo "The password hash in database doesn't match 'admin123'<br>";
        }
    } else {
        echo "❌ Admin user NOT found in database<br>";
        echo "Run the INSERT query from Step 2 above<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>