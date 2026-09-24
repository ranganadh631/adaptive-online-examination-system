<?php

require_once __DIR__ . "/../config/database.php";

$name = "Test Student";
$student_id = "TEST001";
$email = "test@gmail.com";
$mobile = "9999999999";
$password = password_hash("123456", PASSWORD_DEFAULT);

$sql = "INSERT INTO users
        (name, student_id, email, mobile, password)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "sssss",
    $name,
    $student_id,
    $email,
    $mobile,
    $password
);

if ($stmt->execute()) {
    echo "<h2 style='color:green;'>TEST INSERT SUCCESSFUL</h2>";
    echo "Student ID: " . $student_id;
    echo "<br>Email: " . $email;
} else {
    echo "<h2 style='color:red;'>INSERT FAILED</h2>";
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>