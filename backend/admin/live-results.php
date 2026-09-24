<?php

/*
============================================================
LIVE RESULTS API
backend/admin/live-results.php
============================================================
*/

error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once "../config/database.php";


try {

    /*
    ========================================================
    CHECK DATABASE CONNECTION
    ========================================================
    */

    if (!$conn) {
        throw new Exception("Database connection failed.");
    }


    /*
    ========================================================
    GET RESULTS
    ========================================================
    */

    $sql = "
        SELECT

            r.id,
            r.user_id,
            r.exam_id,

            s.name AS student_name,
            s.student_id AS student_code,
            s.email AS student_email,

            e.exam_name,

            r.total_questions,
            r.correct_answers,
            r.wrong_answers,
            r.score,
            r.ability_level,
            r.completed_at

        FROM results r

        LEFT JOIN students s
            ON r.user_id = s.id

        LEFT JOIN exams e
            ON r.exam_id = e.id

        ORDER BY r.completed_at DESC, r.id DESC
    ";


    $result = $conn->query($sql);


    if (!$result) {

        throw new Exception(
            "Results query failed: " .
            $conn->error
        );

    }


    /*
    ========================================================
    BUILD RESULTS ARRAY
    ========================================================
    */

    $results = [];


    while ($row = $result->fetch_assoc()) {

        $results[] = [

            "id" =>
                (int)$row["id"],

            "user_id" =>
                (int)$row["user_id"],

            "exam_id" =>
                (int)$row["exam_id"],


            /*
            STUDENT
            */

            "student_name" =>
                $row["student_name"] ?? "Unknown Student",

            "student_id" =>
                $row["student_code"] ?? "",

            "student_email" =>
                $row["student_email"] ?? "",


            /*
            EXAM
            */

            "exam_name" =>
                $row["exam_name"] ?? "Unknown Examination",


            /*
            RESULT
            */

            "total_questions" =>
                (int)($row["total_questions"] ?? 0),

            "correct_answers" =>
                (int)($row["correct_answers"] ?? 0),

            "wrong_answers" =>
                (int)($row["wrong_answers"] ?? 0),

            "score" =>
                (float)($row["score"] ?? 0),

            "ability_level" =>
                $row["ability_level"] ?? "Not Available",

            "completed_at" =>
                $row["completed_at"] ?? ""

        ];

    }


    /*
    ========================================================
    SEND RESPONSE
    ========================================================
    */

    echo json_encode(

        [
            "success" => true,
            "count" => count($results),
            "results" => $results
        ],

        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES

    );


}
catch (Throwable $e) {


    /*
    ========================================================
    ERROR RESPONSE
    ========================================================
    */

    http_response_code(500);


    echo json_encode(

        [
            "success" => false,
            "count" => 0,
            "results" => [],
            "message" =>
                $e->getMessage()
        ],

        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES

    );

}


if (isset($conn) && $conn) {

    $conn->close();

}

?>