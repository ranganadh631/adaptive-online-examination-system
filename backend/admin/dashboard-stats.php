<?php

session_start();

header("Content-Type: application/json");

require_once "../config/database.php";


// ==============================
// Check Admin Login
// ==============================

if (!isset($_SESSION['admin_id'])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);

    exit();
}


// ==============================
// Total Students
// ==============================

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM students"
);

if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to get students count",
        "error" => $conn->error
    ]);

    exit();
}

$totalStudents = $result->fetch_assoc()['total'];


// ==============================
// Total Exams
// ==============================

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM exams"
);

if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to get exams count",
        "error" => $conn->error
    ]);

    exit();
}

$totalExams = $result->fetch_assoc()['total'];


// ==============================
// Total Questions
// ==============================

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM questions"
);

if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to get questions count",
        "error" => $conn->error
    ]);

    exit();
}

$totalQuestions = $result->fetch_assoc()['total'];


// ==============================
// Recent Exams
// ==============================

$recentExams = [];

$result = $conn->query(
    "SELECT id, exam_name, total_questions, duration, status
     FROM exams
     ORDER BY created_at DESC
     LIMIT 5"
);

if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to get recent exams",
        "error" => $conn->error
    ]);

    exit();
}

while ($row = $result->fetch_assoc()) {

    $recentExams[] = $row;

}


// ==============================
// Send Response
// ==============================

echo json_encode([

    "success" => true,

    "statistics" => [

        "students" => (int) $totalStudents,

        "exams" => (int) $totalExams,

        "questions" => (int) $totalQuestions

    ],

    "recent_exams" => $recentExams

]);


$conn->close();

?>