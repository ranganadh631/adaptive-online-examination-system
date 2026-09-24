<?php

/* ============================================================
   backend/student/my-result.php
   STUDENT - ALL COMPLETED EXAM RESULTS
   ============================================================ */

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once "../config/database.php";


/* ============================================================
   CHECK DATABASE CONNECTION
============================================================ */

if (!isset($conn) || !$conn) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit();
}


/* ============================================================
   CHECK STUDENT LOGIN
============================================================ */

if (
    !isset($_SESSION['student_id']) &&
    !isset($_SESSION['user_id'])
) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Student login required."
    ]);

    exit();
}


/* ============================================================
   GET LOGGED-IN STUDENT ID
============================================================ */

if (isset($_SESSION['student_id'])) {

    $user_id = (int) $_SESSION['student_id'];

} else {

    $user_id = (int) $_SESSION['user_id'];

}


if ($user_id <= 0) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Invalid student session."
    ]);

    exit();
}


/* ============================================================
   GET ALL COMPLETED ATTEMPTS
============================================================ */

$sql = "

    SELECT

        ea.id AS attempt_id,

        ea.user_id,

        ea.exam_id,

        ea.score,

        ea.percentage,

        ea.status,

        ea.end_time,

        s.name AS student_name,

        s.student_id AS student_number,

        e.exam_name,

        e.total_questions AS exam_total_questions,

        COUNT(sa.id) AS answered_questions,

        COALESCE(

            SUM(

                CASE

                    WHEN sa.is_correct = 1

                    THEN 1

                    ELSE 0

                END

            ),

            0

        ) AS correct_answers

    FROM exam_attempts ea


    INNER JOIN students s

        ON s.id = ea.user_id


    INNER JOIN exams e

        ON e.id = ea.exam_id


    LEFT JOIN student_answers sa

        ON sa.attempt_id = ea.id


    WHERE

        ea.user_id = ?

        AND ea.status = 'Completed'


    GROUP BY

        ea.id,

        ea.user_id,

        ea.exam_id,

        ea.score,

        ea.percentage,

        ea.status,

        ea.end_time,

        s.name,

        s.student_id,

        e.exam_name,

        e.total_questions


    ORDER BY

        ea.end_time DESC,

        ea.id DESC

";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "Unable to prepare result query.",

        "database_error" =>
            $conn->error

    ]);

    exit();
}


$stmt->bind_param(
    "i",
    $user_id
);


if (!$stmt->execute()) {

    $error = $stmt->error;

    $stmt->close();

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "Unable to execute result query.",

        "database_error" =>
            $error

    ]);

    exit();
}


$result = $stmt->get_result();


if (!$result) {

    $stmt->close();

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "Unable to read result data."

    ]);

    exit();
}


/* ============================================================
   BUILD ALL RESULTS
============================================================ */

$results = [];


while ($row = $result->fetch_assoc()) {


    $answered_questions =
        (int) $row['answered_questions'];


    $correct_answers =
        (int) $row['correct_answers'];


    /*
     * Prefer the actual number of questions
     * stored for the exam.
     */
    $exam_total_questions =
        (int) $row['exam_total_questions'];


    /*
     * If exam total is unavailable,
     * use answered question count.
     */
    if ($exam_total_questions <= 0) {

        $exam_total_questions =
            $answered_questions;

    }


    /*
     * Wrong answers.
     */
    $wrong_answers =
        $answered_questions -
        $correct_answers;


    if ($wrong_answers < 0) {

        $wrong_answers = 0;

    }


    /*
     * Use percentage stored in exam_attempts.
     */
    $percentage =
        (float) $row['percentage'];


    /*
     * If percentage is missing,
     * calculate it from answers.
     */
    if (
        $percentage <= 0 &&
        $answered_questions > 0
    ) {

        $percentage =
            round(
                (
                    $correct_answers /
                    $answered_questions
                ) * 100,
                2
            );

    }


    /* ========================================================
       ABILITY LEVEL
    ======================================================== */

    if ($percentage >= 80) {

        $ability_level = "Advanced";

    }
    elseif ($percentage >= 50) {

        $ability_level = "Intermediate";

    }
    else {

        $ability_level = "Beginner";

    }


    /* ========================================================
       ADD RESULT
    ======================================================== */

    $results[] = [

        "attempt_id" =>
            (int) $row['attempt_id'],

        "user_id" =>
            (int) $row['user_id'],

        "exam_id" =>
            (int) $row['exam_id'],

        "student_name" =>
            $row['student_name'],

        "student_id" =>
            $row['student_number'],

        "exam_name" =>
            $row['exam_name'],

        "total_questions" =>
            $exam_total_questions,

        "answered_questions" =>
            $answered_questions,

        "correct_answers" =>
            $correct_answers,

        "wrong_answers" =>
            $wrong_answers,

        "score" =>
            $percentage,

        "percentage" =>
            $percentage,

        "ability_level" =>
            $ability_level,

        "status" =>
            $row['status'],

        "completed_at" =>
            $row['end_time']

    ];

}


$stmt->close();


$conn->close();


/* ============================================================
   SUCCESS RESPONSE
============================================================ */

echo json_encode(

    [

        "success" => true,

        "student_id" => $user_id,

        "results" => $results,

        "count" => count($results)

    ],

    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES

);

?>