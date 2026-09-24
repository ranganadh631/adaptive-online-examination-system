<?php

session_start();

require_once "../config/database.php";


// Only POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}


// Get login data
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';


// Check fields
if (empty($email) || empty($password)) {
    die("Email and password are required.");
}


// Find student
$stmt = $conn->prepare(
    "SELECT id, name, student_id, email, mobile, password
     FROM students
     WHERE email = ?
     LIMIT 1"
);


$stmt->bind_param("s", $email);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 1) {

    $student = $result->fetch_assoc();


    // Verify password
    if (password_verify($password, $student['password'])) {


        // Store session
        $_SESSION['student_id'] = $student['id'];

        $_SESSION['student_name'] = $student['name'];

        $_SESSION['student_email'] = $student['email'];

        $_SESSION['student_student_id'] = $student['student_id'];


        // Redirect
        header(
            "Location: ../../frontend/student_dashboard.php"
        );

        exit();


    } else {

        die("Invalid email or password.");

    }


} else {

    die("Invalid email or password.");

}


$stmt->close();

$conn->close();

?>