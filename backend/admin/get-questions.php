<?php

session_start();

header("Content-Type: application/json");

require_once "../config/database.php";


// ============================================================
// CHECK ADMIN LOGIN
// ============================================================

if (!isset($_SESSION['admin_id'])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);

    exit();
}


// ============================================================
// CHECK IF A QUESTION ID WAS REQUESTED
// ============================================================

$question_id = intval($_GET['id'] ?? 0);


// ============================================================
// IF ID EXISTS → GET ONE QUESTION
// ============================================================

if ($question_id > 0) {

    $stmt = $conn->prepare("
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
    ");


    if (!$stmt) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Failed to prepare query"
        ]);

        exit();
    }


    $stmt->bind_param(
        "i",
        $question_id
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    if ($result->num_rows === 0) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Question not found"
        ]);

        $stmt->close();
        $conn->close();

        exit();
    }


    $question =
        $result->fetch_assoc();


    echo json_encode([
        "success" => true,
        "question" => $question
    ]);


    $stmt->close();
    $conn->close();

    exit();
}


// ============================================================
// NO ID → GET ALL QUESTIONS
// Used by manage_questions.html
// ============================================================

$sql = "
    SELECT
        q.id,
        q.exam_id,
        e.exam_name,
        q.topic,
        q.question,
        q.option_a,
        q.option_b,
        q.option_c,
        q.option_d,
        q.correct_answer,
        q.difficulty
    FROM questions q
    LEFT JOIN exams e
        ON q.exam_id = e.id
    ORDER BY q.id DESC
";


$result =
    $conn->query($sql);


if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch questions"
    ]);

    exit();
}


// ============================================================
// STORE QUESTIONS
// ============================================================

$questions = [];


while ($row = $result->fetch_assoc()) {

    $questions[] = $row;

}


// ============================================================
// SEND ALL QUESTIONS
// ============================================================

echo json_encode([
    "success" => true,
    "questions" => $questions,
    "total" => count($questions)
]);


$conn->close();

?>