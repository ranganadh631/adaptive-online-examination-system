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
// ONLY POST REQUEST
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
// GET QUESTION ID
// ==========================================

$id = intval($_POST['id'] ?? 0);


if ($id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid question ID."
    ]);

    exit;
}


// ==========================================
// CHECK QUESTION EXISTS
// ==========================================

$check = $conn->prepare(
    "SELECT id FROM questions WHERE id = ? LIMIT 1"
);


if (!$check) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare question check."
    ]);

    exit;
}


$check->bind_param(
    "i",
    $id
);


$check->execute();


$result = $check->get_result();


if ($result->num_rows === 0) {

    $check->close();

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Question not found."
    ]);

    exit;
}


$check->close();


// ==========================================
// DELETE QUESTION
// ==========================================

$stmt = $conn->prepare(
    "DELETE FROM questions WHERE id = ?"
);


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare delete query."
    ]);

    exit;
}


$stmt->bind_param(
    "i",
    $id
);


// ==========================================
// EXECUTE DELETE
// ==========================================

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Question deleted successfully!"
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete question: " . $stmt->error
    ]);
}


// ==========================================
// CLOSE
// ==========================================

$stmt->close();

$conn->close();

?>