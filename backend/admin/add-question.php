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
        "message" => "Unauthorized access. Please login as admin."
    ]);

    exit;
}


// ==========================================
// ONLY POST REQUEST ALLOWED
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}


// ==========================================
// GET FORM DATA
// ==========================================

$id = intval($_POST['id'] ?? 0);

$exam_id = intval($_POST['exam_id'] ?? 0);

$topic = trim($_POST['topic'] ?? '');

$question = trim($_POST['question'] ?? '');

$option_a = trim($_POST['option_a'] ?? '');

$option_b = trim($_POST['option_b'] ?? '');

$option_c = trim($_POST['option_c'] ?? '');

$option_d = trim($_POST['option_d'] ?? '');

$correct_answer = strtoupper(
    trim($_POST['correct_answer'] ?? '')
);

$difficulty = trim($_POST['difficulty'] ?? '');


// ==========================================
// VALIDATE REQUIRED FIELDS
// ==========================================

if (
    $exam_id <= 0 ||
    empty($topic) ||
    empty($question) ||
    empty($option_a) ||
    empty($option_b) ||
    empty($option_c) ||
    empty($option_d) ||
    empty($correct_answer) ||
    empty($difficulty)
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please fill in all fields correctly."
    ]);

    exit;
}


// ==========================================
// VALIDATE CORRECT ANSWER
// ==========================================

$valid_answers = [
    'A',
    'B',
    'C',
    'D'
];

if (!in_array($correct_answer, $valid_answers, true)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid correct answer."
    ]);

    exit;
}


// ==========================================
// VALIDATE DIFFICULTY
// ==========================================

$valid_difficulties = [
    'Easy',
    'Medium',
    'Hard'
];

if (!in_array($difficulty, $valid_difficulties, true)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid difficulty level."
    ]);

    exit;
}


// ==========================================
// CHECK EXAM EXISTS
// ==========================================

$exam_check = $conn->prepare(
    "SELECT id FROM exams WHERE id = ? LIMIT 1"
);

if (!$exam_check) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare exam validation."
    ]);

    exit;
}

$exam_check->bind_param(
    "i",
    $exam_id
);

$exam_check->execute();

$exam_result = $exam_check->get_result();

if ($exam_result->num_rows === 0) {

    $exam_check->close();

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Selected exam does not exist."
    ]);

    exit;
}

$exam_check->close();


// ============================================================
// UPDATE EXISTING QUESTION
// ============================================================

if ($id > 0) {

    // Check question exists

    $question_check = $conn->prepare(
        "SELECT id FROM questions WHERE id = ? LIMIT 1"
    );

    if (!$question_check) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to check question."
        ]);

        exit;
    }

    $question_check->bind_param(
        "i",
        $id
    );

    $question_check->execute();

    $question_result =
        $question_check->get_result();

    if ($question_result->num_rows === 0) {

        $question_check->close();

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Question not found."
        ]);

        exit;
    }

    $question_check->close();


    // Update

    $sql = "
        UPDATE questions
        SET
            exam_id = ?,
            topic = ?,
            question = ?,
            option_a = ?,
            option_b = ?,
            option_c = ?,
            option_d = ?,
            correct_answer = ?,
            difficulty = ?
        WHERE id = ?
    ";


    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to prepare update."
        ]);

        exit;
    }


    $stmt->bind_param(
        "issssssssi",
        $exam_id,
        $topic,
        $question,
        $option_a,
        $option_b,
        $option_c,
        $option_d,
        $correct_answer,
        $difficulty,
        $id
    );


    if ($stmt->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "Question updated successfully!",
            "question_id" => $id
        ]);

    } else {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Error updating question: " . $stmt->error
        ]);
    }


    $stmt->close();

    $conn->close();

    exit;
}


// ============================================================
// ADD NEW QUESTION
// ============================================================

$sql = "
    INSERT INTO questions
    (
        exam_id,
        topic,
        question,
        option_a,
        option_b,
        option_c,
        option_d,
        correct_answer,
        difficulty
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare question insert."
    ]);

    exit;
}


$stmt->bind_param(
    "issssssss",
    $exam_id,
    $topic,
    $question,
    $option_a,
    $option_b,
    $option_c,
    $option_d,
    $correct_answer,
    $difficulty
);


if ($stmt->execute()) {

    $question_id =
        $stmt->insert_id;

    echo json_encode([
        "success" => true,
        "message" => "Question added successfully!",
        "question_id" => $question_id
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Error adding question: " . $stmt->error
    ]);
}


$stmt->close();

$conn->close();

?>