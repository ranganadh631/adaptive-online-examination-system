<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();

require_once "../config/database.php";

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}

// Get form values
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate
if (empty($username) || empty($password)) {
    die("Username and password are required.");
}

// Find admin
$stmt = $conn->prepare(
    "SELECT id, username, password
     FROM admins
     WHERE username = ?
     LIMIT 1"
);

$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 1) {

    $admin = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $admin['password'])) {

        // Create admin session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        // Redirect to admin dashboard
        header(
            "Location: ../../frontend/admin_dashboard.html"
        );

        exit();

    } else {

        die("Invalid username or password.");

    }

} else {

    die("Invalid username or password.");

}

$stmt->close();
$conn->close();

?>