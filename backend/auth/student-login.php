<?php

session_start();

require_once "../config/database.php";


// ==========================================
// ONLY POST REQUEST
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    die("Invalid request.");
}


// ==========================================
// GET LOGIN DATA
// ==========================================

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';


// ==========================================
// VALIDATE
// ==========================================

if (empty($email) || empty($password)) {

    die("Email and password are required.");
}


// ==========================================
// FIND STUDENT
// ==========================================

$stmt = $conn->prepare(
    "SELECT
        id,
        name,
        student_id,
        email,
        mobile,
        password
     FROM students
     WHERE email = ?
     LIMIT 1"
);


if (!$stmt) {

    die(
        "Database error: " .
        $conn->error
    );
}


$stmt->bind_param(
    "s",
    $email
);


$stmt->execute();


$result = $stmt->get_result();


// ==========================================
// CHECK STUDENT EXISTS
// ==========================================

if ($result->num_rows !== 1) {

    $stmt->close();
    $conn->close();

    die("Invalid email or password.");
}


$student = $result->fetch_assoc();


// ==========================================
// VERIFY PASSWORD
// ==========================================

if (!password_verify(
    $password,
    $student['password']
)) {

    $stmt->close();
    $conn->close();

    die("Invalid email or password.");
}


// ==========================================
// CREATE STUDENT SESSION
// ==========================================

$_SESSION['student_id'] =
    $student['id'];

$_SESSION['student_name'] =
    $student['name'];

$_SESSION['student_code'] =
    $student['student_id'];

$_SESSION['student_email'] =
    $student['email'];


// ==========================================
// CLOSE DATABASE
// ==========================================

$stmt->close();

$conn->close();


// ==========================================
// REDIRECT
// ==========================================

header(
    "Location: ../../frontend/index.php"
);

exit();

?>