<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


/* ============================================================
   START SESSION SAFELY
============================================================ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* ============================================================
   DATABASE
============================================================ */

require_once "../backend/config/database.php";


/* ============================================================
   LOGIN CHECK
============================================================ */

/*
 * A student must be logged in.
 *
 * We support:
 * - $_SESSION['student_id']
 * - $_SESSION['user_id']
 * - $_SESSION['email']
 */

if (
    !isset($_SESSION['student_id']) &&
    !isset($_SESSION['user_id']) &&
    !isset($_SESSION['email'])
) {

    header("Location: login.html");
    exit();

}


/* ============================================================
   GET REAL STUDENT DATABASE ID
============================================================ */

/*
 * IMPORTANT:
 *
 * exam_attempts.user_id references:
 *
 * students.id
 *
 * Therefore we MUST use the numeric students.id here.
 */

$user_id = 0;


/* ============================================================
   METHOD 1
   SESSION student_id IS NUMERIC DATABASE ID
============================================================ */

if (
    isset($_SESSION['student_id']) &&
    is_numeric($_SESSION['student_id'])
) {

    $possible_id =
        (int)$_SESSION['student_id'];


    if ($possible_id > 0) {

        $stmt = $conn->prepare(
            "SELECT id
             FROM students
             WHERE id = ?
             LIMIT 1"
        );


        if (!$stmt) {

            die(
                "Student lookup failed: " .
                $conn->error
            );

        }


        $stmt->bind_param(
            "i",
            $possible_id
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        if ($result->num_rows > 0) {

            $student =
                $result->fetch_assoc();


            $user_id =
                (int)$student['id'];

        }


        $stmt->close();

    }

}


/* ============================================================
   METHOD 2
   SESSION student_id IS ACTUAL STUDENT ID
   Example: ranga123
============================================================ */

if (
    $user_id <= 0 &&
    isset($_SESSION['student_id'])
) {

    $session_student_id =
        trim(
            (string)$_SESSION['student_id']
        );


    if ($session_student_id !== '') {

        $stmt = $conn->prepare(
            "SELECT id
             FROM students
             WHERE student_id = ?
             LIMIT 1"
        );


        if (!$stmt) {

            die(
                "Student lookup failed: " .
                $conn->error
            );

        }


        $stmt->bind_param(
            "s",
            $session_student_id
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        if ($result->num_rows > 0) {

            $student =
                $result->fetch_assoc();


            $user_id =
                (int)$student['id'];

        }


        $stmt->close();

    }

}


/* ============================================================
   METHOD 3
   FIND STUDENT USING EMAIL
============================================================ */

if (
    $user_id <= 0 &&
    isset($_SESSION['email'])
) {

    $session_email =
        trim(
            (string)$_SESSION['email']
        );


    if ($session_email !== '') {

        $stmt = $conn->prepare(
            "SELECT id
             FROM students
             WHERE email = ?
             LIMIT 1"
        );


        if (!$stmt) {

            die(
                "Student lookup failed: " .
                $conn->error
            );

        }


        $stmt->bind_param(
            "s",
            $session_email
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        if ($result->num_rows > 0) {

            $student =
                $result->fetch_assoc();


            $user_id =
                (int)$student['id'];

        }


        $stmt->close();

    }

}


/* ============================================================
   METHOD 4
   SESSION user_id
============================================================ */

if (
    $user_id <= 0 &&
    isset($_SESSION['user_id']) &&
    is_numeric($_SESSION['user_id'])
) {

    $possible_id =
        (int)$_SESSION['user_id'];


    if ($possible_id > 0) {

        $stmt = $conn->prepare(
            "SELECT id
             FROM students
             WHERE id = ?
             LIMIT 1"
        );


        if (!$stmt) {

            die(
                "Student lookup failed: " .
                $conn->error
            );

        }


        $stmt->bind_param(
            "i",
            $possible_id
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        if ($result->num_rows > 0) {

            $student =
                $result->fetch_assoc();


            $user_id =
                (int)$student['id'];

        }


        $stmt->close();

    }

}


/* ============================================================
   FINAL STUDENT CHECK
============================================================ */

if ($user_id <= 0) {

    session_unset();
    session_destroy();

    die(
        "Student account could not be found. " .
        "Please logout and login again."
    );

}


/* ============================================================
   EXAM ID
============================================================ */

if (
    !isset($_GET['exam_id']) ||
    !is_numeric($_GET['exam_id'])
) {

    die("Invalid exam ID.");

}


$exam_id =
    (int)$_GET['exam_id'];


if ($exam_id <= 0) {

    die("Invalid exam ID.");

}


/* ============================================================
   GET EXAM
============================================================ */

$sql = "
    SELECT
        id,
        exam_name,
        description,
        duration,
        total_questions,
        status
    FROM exams
    WHERE id = ?
      AND status = 'Active'
    LIMIT 1
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    die(
        "Exam query failed: " .
        $conn->error
    );

}


$stmt->bind_param(
    "i",
    $exam_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    die(
        "Exam not found or inactive."
    );

}


$exam =
    $result->fetch_assoc();


$stmt->close();


/* ============================================================
   GET ACTUAL NUMBER OF QUESTIONS
============================================================ */

$database_question_count = 0;


$sql = "
    SELECT COUNT(*) AS total
    FROM questions
    WHERE exam_id = ?
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    die(
        "Question count query failed: " .
        $conn->error
    );

}


$stmt->bind_param(
    "i",
    $exam_id
);


$stmt->execute();


$result =
    $stmt->get_result();


$row =
    $result->fetch_assoc();


$database_question_count =
    (int)$row['total'];


$stmt->close();


/* ============================================================
   CHECK QUESTIONS
============================================================ */

if ($database_question_count <= 0) {

    die(
        "No questions have been added to this examination yet. " .
        "Please add questions from the admin panel."
    );

}


/* ============================================================
   DETERMINE TOTAL QUESTIONS
============================================================ */

$total_questions =
    (int)$exam['total_questions'];


if (
    $total_questions <= 0 ||
    $total_questions > $database_question_count
) {

    $total_questions =
        $database_question_count;

}


/* ============================================================
   FIND EXISTING ATTEMPT
============================================================ */

$attempt_id = 0;

$current_difficulty =
    "Medium";

$attempt_start_time =
    null;


$sql = "
    SELECT
        id,
        start_time,
        current_difficulty,
        status
    FROM exam_attempts
    WHERE user_id = ?
      AND exam_id = ?
      AND status = 'In Progress'
    ORDER BY id DESC
    LIMIT 1
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    die(
        "Attempt query failed: " .
        $conn->error
    );

}


$stmt->bind_param(
    "ii",
    $user_id,
    $exam_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if ($result->num_rows > 0) {

    $attempt =
        $result->fetch_assoc();


    $attempt_id =
        (int)$attempt['id'];


    $current_difficulty =
        !empty(
            $attempt['current_difficulty']
        )
        ? $attempt['current_difficulty']
        : "Medium";


    $attempt_start_time =
        $attempt['start_time'];

}


$stmt->close();


/* ============================================================
   CREATE NEW ATTEMPT
============================================================ */

if ($attempt_id <= 0) {

    $current_difficulty =
        "Medium";


    $status =
        "In Progress";


    $sql = "
        INSERT INTO exam_attempts
        (
            user_id,
            exam_id,
            start_time,
            current_difficulty,
            status
        )
        VALUES
        (
            ?,
            ?,
            NOW(),
            ?,
            ?
        )
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        die(
            "Could not create attempt: " .
            $conn->error
        );

    }


    $stmt->bind_param(
        "iiss",
        $user_id,
        $exam_id,
        $current_difficulty,
        $status
    );


    if (!$stmt->execute()) {

        die(
            "Could not start examination: " .
            $stmt->error
        );

    }


    $attempt_id =
        (int)$conn->insert_id;


    $attempt_start_time =
        date(
            "Y-m-d H:i:s"
        );


    $stmt->close();

}


/* ============================================================
   COUNT ANSWERED QUESTIONS
============================================================ */

$sql = "
    SELECT COUNT(*) AS answered
    FROM student_answers
    WHERE attempt_id = ?
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    die(
        "Answer count query failed: " .
        $conn->error
    );

}


$stmt->bind_param(
    "i",
    $attempt_id
);


$stmt->execute();


$result =
    $stmt->get_result();


$row =
    $result->fetch_assoc();


$answered_count =
    (int)$row['answered'];


$stmt->close();


/* ============================================================
   CHECK IF EXAM IS ALREADY FINISHED
============================================================ */

if (
    $answered_count >=
    $total_questions
) {

    header(
        "Location: results.php?attempt_id=" .
        $attempt_id
    );

    exit();

}


/* ============================================================
   GET NEXT ADAPTIVE QUESTION
============================================================ */

$question = null;


/* ============================================================
   FIRST TRY CURRENT DIFFICULTY
============================================================ */

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
        q.difficulty
    FROM questions q
    WHERE q.exam_id = ?
      AND q.difficulty = ?
      AND NOT EXISTS
      (
          SELECT 1
          FROM student_answers sa
          WHERE sa.attempt_id = ?
            AND sa.question_id = q.id
      )
    ORDER BY RAND()
    LIMIT 1
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    die(
        "Question query failed: " .
        $conn->error
    );

}


$stmt->bind_param(
    "isi",
    $exam_id,
    $current_difficulty,
    $attempt_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if ($result->num_rows > 0) {

    $question =
        $result->fetch_assoc();

}


$stmt->close();


/* ============================================================
   FALLBACK - ANY REMAINING QUESTION
============================================================ */

if (!$question) {

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
            q.difficulty
        FROM questions q
        WHERE q.exam_id = ?
          AND NOT EXISTS
          (
              SELECT 1
              FROM student_answers sa
              WHERE sa.attempt_id = ?
                AND sa.question_id = q.id
          )
        ORDER BY RAND()
        LIMIT 1
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        die(
            "Fallback question query failed: " .
            $conn->error
        );

    }


    $stmt->bind_param(
        "ii",
        $exam_id,
        $attempt_id
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    if ($result->num_rows > 0) {

        $question =
            $result->fetch_assoc();

    }


    $stmt->close();

}


/* ============================================================
   NO QUESTION LEFT
============================================================ */

if (!$question) {

    die(
        "No unanswered questions are available for this examination. " .
        "Please check the questions and student_answers tables."
    );

}


/* ============================================================
   TIMER
============================================================ */

$duration_minutes =
    (int)$exam['duration'];


if ($duration_minutes <= 0) {

    $duration_minutes =
        30;

}


$duration_seconds =
    $duration_minutes * 60;


$remaining_seconds =
    $duration_seconds;


if (!empty($attempt_start_time)) {

    $start_timestamp =
        strtotime(
            $attempt_start_time
        );


    if ($start_timestamp !== false) {

        $elapsed =
            time() -
            $start_timestamp;


        $remaining_seconds =
            $duration_seconds -
            $elapsed;

    }

}


if ($remaining_seconds < 0) {

    $remaining_seconds =
        0;

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>

<?php

echo htmlspecialchars(
    $exam['exam_name']
);

?>

</title>


<link
    href="assets/vendor/bootstrap/css/bootstrap.min.css"
    rel="stylesheet">


<link
    href="assets/vendor/bootstrap-icons/bootstrap-icons.css"
    rel="stylesheet">


<style>

body {
    margin: 0;
    background: #f5f7fa;
    font-family: Arial, sans-serif;
}

.exam-header {
    background: white;
    padding: 18px 35px;
    box-shadow: 0 2px 10px rgba(0,0,0,.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.exam-title {
    margin: 0;
    font-size: 22px;
    color: #222;
}

.timer {
    background: #dc3545;
    color: white;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 18px;
    font-weight: bold;
}

.exam-container {
    max-width: 950px;
    margin: 40px auto;
    padding: 0 15px;
}

.exam-info {
    background: white;
    padding: 25px;
    border-radius: 18px;
    box-shadow: 0 5px 20px rgba(0,0,0,.08);
    margin-bottom: 25px;
}

.question-card {
    background: white;
    padding: 35px;
    border-radius: 18px;
    box-shadow: 0 5px 20px rgba(0,0,0,.08);
}

.question-number {
    color: #0d6efd;
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 15px;
}

.question-text {
    font-size: 21px;
    font-weight: 600;
    line-height: 1.5;
    margin-bottom: 25px;
}

.option {
    display: block;
    border: 2px solid #e1e5ea;
    padding: 16px;
    margin-bottom: 15px;
    border-radius: 10px;
    cursor: pointer;
}

.option:hover {
    border-color: #0d6efd;
    background: #f0f6ff;
}

.option input {
    margin-right: 10px;
}

.difficulty {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
    background: #fff3cd;
    color: #856404;
}

.submit-btn {
    width: 100%;
    padding: 13px;
    font-size: 17px;
    font-weight: bold;
    margin-top: 10px;
}

</style>

</head>


<body>


<header class="exam-header">

    <h1 class="exam-title">

        <i class="bi bi-mortarboard-fill text-primary"></i>

        <?php

        echo htmlspecialchars(
            $exam['exam_name']
        );

        ?>

    </h1>


    <div
        class="timer"
        id="timer">

        00:00

    </div>

</header>


<div class="exam-container">


    <!-- ====================================================
         EXAM INFORMATION
    ===================================================== -->

    <div class="exam-info">

        <div class="d-flex justify-content-between">

            <div>

                <h2>

                    <?php

                    echo htmlspecialchars(
                        $exam['exam_name']
                    );

                    ?>

                </h2>


                <p class="text-muted mb-0">

                    <?php

                    echo !empty(
                        $exam['description']
                    )

                    ? htmlspecialchars(
                        $exam['description']
                    )

                    : "Adaptive examination";

                    ?>

                </p>

            </div>


            <div class="text-end">

                <strong>

                    Question

                    <?php

                    echo $answered_count + 1;

                    ?>

                    /

                    <?php

                    echo $total_questions;

                    ?>

                </strong>


                <br>


                <span class="difficulty mt-2">

                    Difficulty:

                    <?php

                    echo htmlspecialchars(
                        $question['difficulty']
                    );

                    ?>

                </span>

            </div>

        </div>

    </div>


    <!-- ====================================================
         QUESTION
    ===================================================== -->

    <div class="question-card">


        <div class="question-number">

            Question

            <?php

            echo $answered_count + 1;

            ?>

        </div>


        <div class="question-text">

            <?php

            echo htmlspecialchars(
                $question['question']
            );

            ?>

        </div>


        <form id="answerForm">


            <input
                type="hidden"
                name="attempt_id"
                value="<?php
                echo $attempt_id;
                ?>">


            <input
                type="hidden"
                name="exam_id"
                value="<?php
                echo $exam_id;
                ?>">


            <input
                type="hidden"
                name="question_id"
                value="<?php
                echo $question['id'];
                ?>">


            <!-- OPTION A -->

            <label class="option">

                <input
                    type="radio"
                    name="selected_answer"
                    value="A">

                <strong>A.</strong>

                <?php

                echo htmlspecialchars(
                    $question['option_a']
                );

                ?>

            </label>


            <!-- OPTION B -->

            <label class="option">

                <input
                    type="radio"
                    name="selected_answer"
                    value="B">

                <strong>B.</strong>

                <?php

                echo htmlspecialchars(
                    $question['option_b']
                );

                ?>

            </label>


            <!-- OPTION C -->

            <label class="option">

                <input
                    type="radio"
                    name="selected_answer"
                    value="C">

                <strong>C.</strong>

                <?php

                echo htmlspecialchars(
                    $question['option_c']
                );

                ?>

            </label>


            <!-- OPTION D -->

            <label class="option">

                <input
                    type="radio"
                    name="selected_answer"
                    value="D">

                <strong>D.</strong>

                <?php

                echo htmlspecialchars(
                    $question['option_d']
                );

                ?>

            </label>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="btn btn-primary submit-btn"
                id="submitBtn">

                Submit Answer

                <i class="bi bi-arrow-right"></i>

            </button>


        </form>

    </div>

</div>


<script>

/* ============================================================
   SUBMIT ANSWER
============================================================ */

document
.getElementById("answerForm")
.addEventListener(
    "submit",
    function(event) {

        event.preventDefault();


        const selected =
            document.querySelector(
                'input[name="selected_answer"]:checked'
            );


        if (!selected) {

            alert(
                "Please select an answer."
            );

            return;

        }


        const submitButton =
            document.getElementById(
                "submitBtn"
            );


        submitButton.disabled =
            true;


        submitButton.innerHTML =
            "Checking answer...";


        const formData =
            new FormData(this);


        fetch(
            "submit_exam.php",
            {
                method: "POST",
                body: formData
            }
        )

        .then(
            async response => {

                const text =
                    await response.text();


                let data;


                try {

                    data =
                        JSON.parse(text);

                }

                catch (e) {

                    console.error(
                        "Server response:",
                        text
                    );


                    throw new Error(
                        "Invalid server response: " +
                        text
                    );

                }


                return data;

            }
        )


        .then(
            data => {

                console.log(
                    "Submit response:",
                    data
                );


                if (data.success) {


                    if (data.completed) {

                        window.location.href =
                            "results.php?attempt_id=" +
                            data.attempt_id;

                    }

                    else {

                        window.location.href =
                            "exam.php?exam_id=" +
                            data.exam_id;

                    }


                }

                else {

                    alert(
                        data.message ||
                        "Unable to submit answer."
                    );


                    submitButton.disabled =
                        false;


                    submitButton.innerHTML =
                        'Submit Answer <i class="bi bi-arrow-right"></i>';

                }

            }
        )


        .catch(
            error => {

                console.error(
                    "Submit error:",
                    error
                );


                alert(
                    "Something went wrong while submitting the answer.\n\n" +
                    error.message
                );


                submitButton.disabled =
                    false;


                submitButton.innerHTML =
                    'Submit Answer <i class="bi bi-arrow-right"></i>';

            }
        );

    }
);


/* ============================================================
   TIMER
============================================================ */

let timeLeft =
    <?php

    echo (int)$remaining_seconds;

    ?>;


const timer =
    document.getElementById(
        "timer"
    );


function updateTimer() {

    let minutes =
        Math.floor(
            timeLeft / 60
        );


    let seconds =
        timeLeft % 60;


    minutes =
        String(minutes)
        .padStart(2, "0");


    seconds =
        String(seconds)
        .padStart(2, "0");


    timer.textContent =
        minutes + ":" + seconds;


    if (timeLeft <= 60) {

        timer.style.background =
            "#fd7e14";

    }


    if (timeLeft <= 0) {

        clearInterval(
            timerInterval
        );


        alert(
            "Time is over. Please submit the current answer."
        );


        return;

    }


    timeLeft--;

}


updateTimer();


const timerInterval =
    setInterval(
        updateTimer,
        1000
    );

</script>


</body>

</html>