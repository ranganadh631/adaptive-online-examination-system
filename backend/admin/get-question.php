<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";


// ==========================================
// CHECK ADMIN LOGIN
// ==========================================

if (!isset($_SESSION['admin_id'])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);

    exit;
}


// ==========================================
// GET QUESTION ID
// ==========================================

$id = intval($_GET['id'] ?? 0);


if ($id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid question ID."
    ]);

    exit;
}


// ==========================================
// GET QUESTION
// ==========================================

$sql = "
    SELECT
        q.id,
        q.exam_id,
        q.topic,
        q.question,
        q.option_a,
        q.option_b,
        q.option_c,
        q.option_d,
        q.correct_answer,
        q.difficulty,
        e.exam_name
    FROM questions q
    LEFT JOIN exams e
        ON q.exam_id = e.id
    WHERE q.id = ?
    LIMIT 1
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database query preparation failed."
    ]);

    exit;
}


$stmt->bind_param("i", $id);


if (!$stmt->execute()) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to execute query."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


$result = $stmt->get_result();


if ($result->num_rows === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Question not found."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


// ==========================================
// GET QUESTION DATA
// ==========================================

$question = $result->fetch_assoc();


// ==========================================
// RESPONSE
// ==========================================

echo json_encode([
    "success" => true,
    "question" => $question
]);


$stmt->close();
$conn->close();

?>