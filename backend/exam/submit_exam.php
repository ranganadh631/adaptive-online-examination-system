<?php

/*
============================================================
frontend/submit_exam.php
FINAL EXAM ANSWER SUBMISSION
============================================================
*/

session_start();

/*
============================================================
PHP ERROR HANDLING
============================================================
*/

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");


/*
============================================================
DATABASE
============================================================
*/

require_once "../backend/config/database.php";


/*
============================================================
JSON RESPONSE FUNCTION
============================================================
*/

function sendResponse($data, $status = 200)
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
============================================================
DATABASE CONNECTION CHECK
============================================================
*/

if (!isset($conn) || !$conn) {

    sendResponse([
        "success" => false,
        "message" => "Database connection failed."
    ], 500);

}


/*
============================================================
LOGIN CHECK
============================================================
*/

if (
    !isset($_SESSION['student_id']) &&
    !isset($_SESSION['user_id'])
) {

    sendResponse([
        "success" => false,
        "message" => "Student login required. Please login again."
    ], 401);

}


if (isset($_SESSION['student_id'])) {

    $user_id = (int) $_SESSION['student_id'];

} else {

    $user_id = (int) $_SESSION['user_id'];

}


if ($user_id <= 0) {

    sendResponse([
        "success" => false,
        "message" => "Invalid student session."
    ], 401);

}


/*
============================================================
REQUEST METHOD
============================================================
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    sendResponse([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ], 405);

}


/*
============================================================
GET POST DATA
============================================================
*/

$attempt_id = isset($_POST['attempt_id'])
    ? (int) $_POST['attempt_id']
    : 0;

$exam_id = isset($_POST['exam_id'])
    ? (int) $_POST['exam_id']
    : 0;

$question_id = isset($_POST['question_id'])
    ? (int) $_POST['question_id']
    : 0;

$selected_answer = isset($_POST['selected_answer'])
    ? strtoupper(trim((string)$_POST['selected_answer']))
    : "";


/*
============================================================
VALIDATION
============================================================
*/

if ($attempt_id <= 0) {

    sendResponse([
        "success" => false,
        "message" => "Invalid attempt ID."
    ], 400);

}


if ($exam_id <= 0) {

    sendResponse([
        "success" => false,
        "message" => "Invalid exam ID."
    ], 400);

}


if ($question_id <= 0) {

    sendResponse([
        "success" => false,
        "message" => "Invalid question ID."
    ], 400);

}


if (!in_array(
    $selected_answer,
    ["A", "B", "C", "D"],
    true
)) {

    sendResponse([
        "success" => false,
        "message" => "Invalid selected answer."
    ], 400);

}


/*
============================================================
START TRANSACTION
============================================================
*/

$conn->begin_transaction();


try {


    /*
    ========================================================
    VERIFY EXAM ATTEMPT
    ========================================================
    */

    $sql = "
        SELECT
            id,
            user_id,
            exam_id,
            current_difficulty,
            status
        FROM exam_attempts
        WHERE id = ?
          AND user_id = ?
          AND exam_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "Attempt query prepare failed: " .
            $conn->error
        );

    }

    $stmt->bind_param(
        "iii",
        $attempt_id,
        $user_id,
        $exam_id
    );

    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        throw new Exception(
            "Attempt query failed: " .
            $error
        );

    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();

        throw new Exception(
            "Invalid examination attempt."
        );

    }

    $attempt = $result->fetch_assoc();

    $stmt->close();


    /*
    ========================================================
    CHECK STATUS
    ========================================================
    */

    if (
        !isset($attempt['status']) ||
        $attempt['status'] !== "In Progress"
    ) {

        throw new Exception(
            "This examination has already been completed."
        );

    }


    /*
    ========================================================
    GET QUESTION
    ========================================================
    */

    $sql = "
        SELECT
            id,
            exam_id,
            correct_answer,
            difficulty
        FROM questions
        WHERE id = ?
          AND exam_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "Question query prepare failed: " .
            $conn->error
        );

    }

    $stmt->bind_param(
        "ii",
        $question_id,
        $exam_id
    );

    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        throw new Exception(
            "Question query failed: " .
            $error
        );

    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();

        throw new Exception(
            "Question not found."
        );

    }

    $question = $result->fetch_assoc();

    $stmt->close();


    /*
    ========================================================
    CHECK DUPLICATE ANSWER
    ========================================================
    */

    $sql = "
        SELECT id
        FROM student_answers
        WHERE attempt_id = ?
          AND question_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "Duplicate answer check prepare failed: " .
            $conn->error
        );

    }

    $stmt->bind_param(
        "ii",
        $attempt_id,
        $question_id
    );

    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        throw new Exception(
            "Duplicate answer query failed: " .
            $error
        );

    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $stmt->close();

        throw new Exception(
            "This question has already been answered."
        );

    }

    $stmt->close();


    /*
    ========================================================
    CHECK ANSWER
    ========================================================
    */

    $correct_answer = strtoupper(
        trim(
            (string)$question['correct_answer']
        )
    );

    $is_correct =
        ($selected_answer === $correct_answer)
        ? 1
        : 0;


    /*
    ========================================================
    SAVE ANSWER
    ========================================================
    */

    $sql = "
        INSERT INTO student_answers
        (
            attempt_id,
            question_id,
            selected_answer,
            is_correct,
            answered_at
        )
        VALUES
        (?, ?, ?, ?, NOW())
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "Answer insert prepare failed: " .
            $conn->error
        );

    }

    $stmt->bind_param(
        "iisi",
        $attempt_id,
        $question_id,
        $selected_answer,
        $is_correct
    );

    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        throw new Exception(
            "Answer insert failed: " .
            $error
        );

    }

    $stmt->close();


    /*
    ========================================================
    CALCULATE NEW DIFFICULTY
    ========================================================
    */

    $old_difficulty = ucfirst(
        strtolower(
            trim(
                (string)$question['difficulty']
            )
        )
    );

    $new_difficulty = $old_difficulty;


    if ($is_correct) {

        if ($old_difficulty === "Easy") {

            $new_difficulty = "Medium";

        } elseif ($old_difficulty === "Medium") {

            $new_difficulty = "Hard";

        } else {

            $new_difficulty = "Hard";

        }

    } else {

        if ($old_difficulty === "Hard") {

            $new_difficulty = "Medium";

        } elseif ($old_difficulty === "Medium") {

            $new_difficulty = "Easy";

        } else {

            $new_difficulty = "Easy";

        }

    }


    /*
    ========================================================
    UPDATE CURRENT DIFFICULTY
    ========================================================
    */

    $sql = "
        UPDATE exam_attempts
        SET current_difficulty = ?
        WHERE id = ?
          AND user_id = ?
          AND exam_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "Difficulty update prepare failed: " .
            $conn->error
        );

    }

    $stmt->bind_param(
        "siii",
        $new_difficulty,
        $attempt_id,
        $user_id,
        $exam_id
    );

    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        throw new Exception(
            "Difficulty update failed: " .
            $error
        );

    }

    $stmt->close();


    /*
    ========================================================
    GET EXAM TOTAL QUESTIONS
    ========================================================
    */

    $sql = "
        SELECT
            total_questions
        FROM exams
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "Exam query prepare failed: " .
            $conn->error
        );

    }

    $stmt->bind_param(
        "i",
        $exam_id
    );

    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        throw new Exception(
            "Exam query failed: " .
            $error
        );

    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();

        throw new Exception(
            "Exam not found."
        );

    }

    $exam_data = $result->fetch_assoc();

    $stmt->close();


    /*
    ========================================================
    TOTAL QUESTIONS
    ========================================================
    */

    $total_questions =
        isset($exam_data['total_questions'])
        ? (int)$exam_data['total_questions']
        : 0;


    if ($total_questions <= 0) {

        throw new Exception(
            "Invalid total questions configured for this exam."
        );

    }


    /*
    ========================================================
    COUNT ANSWERS
    ========================================================
    */

    $sql = "
        SELECT
            COUNT(*) AS answered
        FROM student_answers
        WHERE attempt_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "Answer count prepare failed: " .
            $conn->error
        );

    }

    $stmt->bind_param(
        "i",
        $attempt_id
    );

    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        throw new Exception(
            "Answer count failed: " .
            $error
        );

    }

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $stmt->close();


    $answered_count =
        isset($row['answered'])
        ? (int)$row['answered']
        : 0;


    /*
    ========================================================
    DEFAULT VALUES
    ========================================================
    */

    $completed = false;

    $correct_answers = 0;

    $wrong_answers = 0;

    $percentage = 0;

    $ability_level = "Beginner";


    /*
    ========================================================
    FINAL QUESTION CHECK
    ========================================================
    */

    if ($answered_count >= $total_questions) {

        $completed = true;


        /*
        ====================================================
        CALCULATE FINAL SCORE
        ====================================================
        */

        $sql = "
            SELECT
                COUNT(*) AS total_answered,
                COALESCE(SUM(is_correct), 0) AS correct_answers
            FROM student_answers
            WHERE attempt_id = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            throw new Exception(
                "Final score prepare failed: " .
                $conn->error
            );

        }

        $stmt->bind_param(
            "i",
            $attempt_id
        );

        if (!$stmt->execute()) {

            $error = $stmt->error;

            $stmt->close();

            throw new Exception(
                "Final score query failed: " .
                $error
            );

        }

        $result = $stmt->get_result();

        $score_data = $result->fetch_assoc();

        $stmt->close();


        $total_answered =
            (int)$score_data['total_answered'];

        $correct_answers =
            (int)$score_data['correct_answers'];

        $wrong_answers =
            $total_answered -
            $correct_answers;


        /*
        ====================================================
        PERCENTAGE
        ====================================================
        */

        if ($total_answered > 0) {

            $percentage = round(
                (
                    $correct_answers /
                    $total_answered
                ) * 100,
                2
            );

        }


        /*
        ====================================================
        ABILITY LEVEL
        ====================================================
        */

        if ($percentage >= 80) {

            $ability_level = "Advanced";

        } elseif ($percentage >= 50) {

            $ability_level = "Intermediate";

        } else {

            $ability_level = "Beginner";

        }


        /*
        ====================================================
        UPDATE EXAM ATTEMPT
        ====================================================
        */

        $sql = "
            UPDATE exam_attempts
            SET
                status = 'Completed',
                end_time = NOW(),
                score = ?,
                percentage = ?
            WHERE id = ?
              AND user_id = ?
              AND exam_id = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            throw new Exception(
                "Attempt completion prepare failed: " .
                $conn->error
            );

        }

        $stmt->bind_param(
            "ddiii",
            $percentage,
            $percentage,
            $attempt_id,
            $user_id,
            $exam_id
        );

        if (!$stmt->execute()) {

            $error = $stmt->error;

            $stmt->close();

            throw new Exception(
                "Attempt completion failed: " .
                $error
            );

        }

        $stmt->close();


        /*
        ====================================================
        CHECK EXISTING RESULT
        ====================================================
        */

        $sql = "
            SELECT id
            FROM results
            WHERE attempt_id = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            throw new Exception(
                "Result check prepare failed: " .
                $conn->error
            );

        }

        $stmt->bind_param(
            "i",
            $attempt_id
        );

        if (!$stmt->execute()) {

            $error = $stmt->error;

            $stmt->close();

            throw new Exception(
                "Result check failed: " .
                $error
            );

        }

        $result = $stmt->get_result();

        $existing_result =
            $result->fetch_assoc();

        $stmt->close();


        /*
        ====================================================
        SAVE RESULT
        ====================================================
        */

        if ($existing_result) {

            /*
            ------------------------------------------------
            UPDATE EXISTING RESULT
            ------------------------------------------------
            */

            $result_id =
                (int)$existing_result['id'];

            $sql = "
                UPDATE results
                SET
                    user_id = ?,
                    exam_id = ?,
                    total_questions = ?,
                    correct_answers = ?,
                    wrong_answers = ?,
                    score = ?,
                    ability_level = ?,
                    completed_at = NOW()
                WHERE id = ?
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {

                throw new Exception(
                    "Result update prepare failed: " .
                    $conn->error
                );

            }

            $stmt->bind_param(
                "iiiiidsi",
                $user_id,
                $exam_id,
                $total_questions,
                $correct_answers,
                $wrong_answers,
                $percentage,
                $ability_level,
                $result_id
            );

            if (!$stmt->execute()) {

                $error = $stmt->error;

                $stmt->close();

                throw new Exception(
                    "Result update failed: " .
                    $error
                );

            }

            $stmt->close();


        } else {

            /*
            ------------------------------------------------
            INSERT NEW RESULT
            ------------------------------------------------
            */

            $sql = "
                INSERT INTO results
                (
                    attempt_id,
                    user_id,
                    exam_id,
                    total_questions,
                    correct_answers,
                    wrong_answers,
                    score,
                    ability_level,
                    completed_at
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {

                throw new Exception(
                    "Result insert prepare failed: " .
                    $conn->error
                );

            }

            /*
            -----------------------------------------------
            8 values:
            
            attempt_id       = integer
            user_id          = integer
            exam_id          = integer
            total_questions  = integer
            correct_answers  = integer
            wrong_answers    = integer
            score            = decimal
            ability_level    = string
            -----------------------------------------------
            */

            $stmt->bind_param(
                "iiiiiids",
                $attempt_id,
                $user_id,
                $exam_id,
                $total_questions,
                $correct_answers,
                $wrong_answers,
                $percentage,
                $ability_level
            );

            if (!$stmt->execute()) {

                $error = $stmt->error;

                $stmt->close();

                throw new Exception(
                    "Result insert failed: " .
                    $error
                );

            }

            $stmt->close();

        }

    }


    /*
    ========================================================
    COMMIT
    ========================================================
    */

    $conn->commit();


    /*
    ========================================================
    CLOSE DATABASE
    ========================================================
    */

    $conn->close();


    /*
    ========================================================
    FINAL SUCCESS RESPONSE
    ========================================================
    */

    sendResponse([

        "success" => true,

        "message" =>
            $completed
            ? "Examination completed successfully."
            : (
                $is_correct
                ? "Correct answer!"
                : "Answer recorded."
            ),

        "correct" =>
            (bool)$is_correct,

        "correct_answer" =>
            $correct_answer,

        "selected_answer" =>
            $selected_answer,

        "new_difficulty" =>
            $new_difficulty,

        "answered" =>
            $answered_count,

        "total_questions" =>
            $total_questions,

        "completed" =>
            $completed,

        "attempt_id" =>
            $attempt_id,

        "exam_id" =>
            $exam_id,

        "correct_answers" =>
            $correct_answers,

        "wrong_answers" =>
            $wrong_answers,

        "percentage" =>
            $percentage,

        "ability_level" =>
            $ability_level

    ], 200);


} catch (Throwable $e) {


    /*
    ========================================================
    ROLLBACK
    ========================================================
    */

    if ($conn) {

        $conn->rollback();

    }


    /*
    ========================================================
    LOG SERVER ERROR
    ========================================================
    */

    error_log(
        "submit_exam.php ERROR: " .
        $e->getMessage()
    );


    /*
    ========================================================
    CLOSE DATABASE
    ========================================================
    */

    if ($conn) {

        $conn->close();

    }


    /*
    ========================================================
    ALWAYS RETURN VALID JSON
    ========================================================
    */

    sendResponse([

        "success" => false,

        "message" =>
            "Exam submission failed.",

        "error" =>
            $e->getMessage(),

        "attempt_id" =>
            $attempt_id,

        "exam_id" =>
            $exam_id,

        "question_id" =>
            $question_id

    ], 500);

}

?>