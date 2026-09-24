<?php

session_start();

require_once "../config/database.php";


// ==============================
// Check Admin Login
// ==============================

if (!isset($_SESSION['admin_id'])) {

    die("Unauthorized access. Please login as admin.");

}


// ==============================
// Only Allow POST Requests
// ==============================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    die("Invalid request.");

}


// ==============================
// Get Form Data
// ==============================

$exam_name = trim($_POST['exam_name'] ?? '');

$description = trim($_POST['description'] ?? '');

$duration = intval($_POST['duration'] ?? 0);

$total_questions = intval(
    $_POST['total_questions'] ?? 0
);

$status = $_POST['status'] ?? 'Active';


// ==============================
// Validate
// ==============================

if (
    empty($exam_name) ||
    empty($description) ||
    $duration <= 0 ||
    $total_questions <= 0
) {

    die("Please fill in all fields correctly.");

}


// ==============================
// Validate Status
// ==============================

if (
    $status !== 'Active' &&
    $status !== 'Inactive'
) {

    $status = 'Active';

}


// ==============================
// Prepare Insert
// ==============================

$stmt = $conn->prepare(
    "INSERT INTO exams
    (
        exam_name,
        description,
        duration,
        total_questions,
        status
    )
    VALUES (?, ?, ?, ?, ?)"
);


if (!$stmt) {

    die(
        "Database error: " .
        $conn->error
    );

}


// ==============================
// Bind Parameters
// ==============================

$stmt->bind_param(
    "ssiis",
    $exam_name,
    $description,
    $duration,
    $total_questions,
    $status
);


// ==============================
// Execute
// ==============================

if ($stmt->execute()) {

    // Exam created successfully

    header(
        "Location: ../../frontend/admin_dashboard.html?success=exam_created"
    );

    exit();

}


else {

    echo "Error creating exam: " .
         $stmt->error;

}


// ==============================
// Close
// ==============================

$stmt->close();

$conn->close();

?>