<?php

session_start();

require_once "../config/database.php";

header("Content-Type: application/json");


// Get all registered students

$sql = "
    SELECT
        id,
        name,
        student_id,
        email,
        mobile,
        created_at
    FROM students
    ORDER BY id DESC
";


$result = $conn->query($sql);


if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to load students."
    ]);

    exit;

}


$students = [];


while ($row = $result->fetch_assoc()) {

    $students[] = $row;

}


echo json_encode([
    "success" => true,
    "students" => $students,
    "count" => count($students)
]);


$conn->close();

?>