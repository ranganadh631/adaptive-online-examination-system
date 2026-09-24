<?php

session_start();

header("Content-Type: application/json");

require_once "../config/database.php";


// Check admin login
if (!isset($_SESSION['admin_id'])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);

    exit();

}


// Get exams
$sql = "
    SELECT
        id,
        exam_name,
        description,
        duration,
        total_questions,
        status
    FROM exams
    ORDER BY id DESC
";


$result = $conn->query($sql);


if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch exams"
    ]);

    exit();

}


$exams = [];


while ($row = $result->fetch_assoc()) {

    $exams[] = $row;

}


echo json_encode([
    "success" => true,
    "exams" => $exams,
    "total" => count($exams)
]);


$conn->close();

?>