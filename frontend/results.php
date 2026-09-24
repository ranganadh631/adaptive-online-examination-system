```php
<?php

/* ============================================================
   frontend/results.php
   STUDENT - ALL EXAMINATION RESULTS
   ============================================================ */

session_start();

require_once "../backend/config/database.php";


/* ============================================================
   CHECK STUDENT LOGIN
============================================================ */

if (!isset($_SESSION['student_id'])) {

    header("Location: student_login.html");
    exit();

}

$student_id = (int)$_SESSION['student_id'];


if ($student_id <= 0) {

    session_destroy();

    header("Location: student_login.html");
    exit();

}


/* ============================================================
   GET STUDENT DETAILS
============================================================ */

$student_sql = "
    SELECT
        id,
        name,
        student_id,
        email,
        mobile
    FROM students
    WHERE id = ?
    LIMIT 1
";

$student_stmt = $conn->prepare($student_sql);

if (!$student_stmt) {

    die(
        "Student query failed: " .
        htmlspecialchars($conn->error)
    );

}

$student_stmt->bind_param(
    "i",
    $student_id
);

$student_stmt->execute();

$student_result =
    $student_stmt->get_result();


if ($student_result->num_rows === 0) {

    $student_stmt->close();

    session_destroy();

    header("Location: student_login.html");

    exit();

}


$student =
    $student_result->fetch_assoc();


$student_name =
    $student['name'];

$student_number =
    $student['student_id'];

$student_email =
    $student['email'];

$student_mobile =
    $student['mobile'];


$student_stmt->close();


/* ============================================================
   GET ALL COMPLETED EXAMINATION ATTEMPTS
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

    INNER JOIN exams e
        ON e.id = ea.exam_id

    LEFT JOIN student_answers sa
        ON sa.attempt_id = ea.id

    WHERE ea.user_id = ?
      AND ea.status = 'Completed'

    GROUP BY

        ea.id,
        ea.user_id,
        ea.exam_id,
        ea.score,
        ea.percentage,
        ea.status,
        ea.end_time,
        e.exam_name,
        e.total_questions

    ORDER BY
        ea.end_time DESC,
        ea.id DESC
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        "Result query failed: " .
        htmlspecialchars($conn->error)
    );

}


$stmt->bind_param(
    "i",
    $student_id
);


if (!$stmt->execute()) {

    die(
        "Unable to load results: " .
        htmlspecialchars($stmt->error)
    );

}


$result =
    $stmt->get_result();


$results = [];


/* ============================================================
   BUILD RESULT DATA
============================================================ */

while ($row = $result->fetch_assoc()) {


    $answered_questions =
        (int)$row['answered_questions'];


    $correct_answers =
        (int)$row['correct_answers'];


    /*
     * Use the exam's configured question count.
     */

    $total_questions =
        (int)$row['exam_total_questions'];


    /*
     * Fallback if exam question count is unavailable.
     */

    if ($total_questions <= 0) {

        $total_questions =
            $answered_questions;

    }


    $wrong_answers =
        max(
            0,
            $total_questions -
            $correct_answers
        );


    /*
     * Final percentage.
     */

    $percentage =
        (float)$row['percentage'];


    /*
     * Ability level.
     */

    if ($percentage >= 80) {

        $ability_level =
            "Advanced";

    }
    elseif ($percentage >= 50) {

        $ability_level =
            "Intermediate";

    }
    else {

        $ability_level =
            "Beginner";

    }


    $results[] = [

        "attempt_id" =>
            (int)$row['attempt_id'],

        "exam_id" =>
            (int)$row['exam_id'],

        "exam_name" =>
            $row['exam_name'],

        "total_questions" =>
            $total_questions,

        "answered_questions" =>
            $answered_questions,

        "correct_answers" =>
            $correct_answers,

        "wrong_answers" =>
            $wrong_answers,

        "score" =>
            $percentage,

        "ability_level" =>
            $ability_level,

        "completed_at" =>
            $row['end_time']

    ];

}


$stmt->close();

$conn->close();

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        All Examination Results - Adaptive Exam
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f7fb;

            color: #1f2937;

        }


        /* =====================================================
           HEADER
        ===================================================== */

        header {

            min-height: 70px;

            background: #ffffff;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            padding:
                10px 35px;

            gap: 20px;

            flex-wrap: wrap;

        }


        .logo {

            text-decoration: none;

            font-size: 22px;

            font-weight: 700;

            color: #2563eb;

            white-space: nowrap;

        }


        .logo span {

            color: #111827;

        }


        nav {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            flex-wrap: wrap;

        }


        nav a {

            text-decoration: none;

            color: #374151;

            padding:
                9px 13px;

            border-radius: 7px;

            font-size: 13px;

            font-weight: 600;

        }


        nav a:hover {

            background: #f3f4f6;

        }


        nav a.active {

            color: #2563eb;

            background: #eff6ff;

        }


        .student-info {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .welcome {

            font-size: 14px;

            color: #6b7280;

        }


        .avatar {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            background: #2563eb;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: bold;

        }


        .logout-btn {

            border: none;

            background: #fee2e2;

            color: #dc2626;

            padding:
                9px 16px;

            border-radius: 7px;

            cursor: pointer;

            font-weight: 600;

        }


        .logout-btn:hover {

            background: #fecaca;

        }


        /* =====================================================
           MAIN
        ===================================================== */

        main {

            max-width: 1400px;

            margin: 0 auto;

            padding:
                35px 25px;

        }


        .page-header {

            margin-bottom: 25px;

        }


        .page-header h1 {

            margin:
                0 0 7px 0;

            font-size: 28px;

            color: #111827;

        }


        .page-header p {

            margin: 0;

            color: #9ca3af;

            font-size: 14px;

        }


        /* =====================================================
           SUMMARY
        ===================================================== */

        .summary {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 18px;

            margin-bottom: 25px;

        }


        .summary-card {

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 12px;

            padding: 20px;

            box-shadow:
                0 3px 10px
                rgba(0,0,0,0.04);

        }


        .summary-label {

            color: #64748b;

            font-size: 13px;

            margin-bottom: 8px;

        }


        .summary-value {

            font-size: 28px;

            font-weight: 700;

            color: #111827;

        }


        /* =====================================================
           RESULTS CARD
        ===================================================== */

        .results-card {

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 14px;

            box-shadow:
                0 3px 10px
                rgba(0,0,0,0.04);

            overflow: hidden;

        }


        .results-header {

            padding: 25px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 15px;

        }


        .results-header h2 {

            margin:
                0 0 5px 0;

            font-size: 20px;

            color: #111827;

        }


        .results-header p {

            margin: 0;

            color: #9ca3af;

            font-size: 13px;

        }


        .student-label {

            font-size: 13px;

            color: #64748b;

            text-align: right;

        }


        .student-label strong {

            color: #111827;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table-container {

            width: 100%;

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 950px;

        }


        thead {

            background: #f9fafb;

        }


        th {

            text-align: left;

            padding:
                16px 18px;

            font-size: 12px;

            color: #64748b;

            font-weight: 700;

            border-bottom:
                1px solid #e5e7eb;

            white-space: nowrap;

        }


        td {

            padding:
                18px;

            font-size: 14px;

            border-bottom:
                1px solid #f0f0f0;

            white-space: nowrap;

        }


        tbody tr:hover {

            background: #fafafa;

        }


        .number {

            color: #64748b;

            font-weight: 600;

        }


        .exam-name {

            font-weight: 700;

            color: #111827;

        }


        .attempt-id {

            margin-top: 4px;

            font-size: 11px;

            color: #9ca3af;

        }


        .correct {

            color: #16a34a;

            font-weight: 700;

        }


        .wrong {

            color: #dc2626;

            font-weight: 700;

        }


        .score {

            color: #2563eb;

            font-weight: 700;

            font-size: 16px;

        }


        .ability {

            display: inline-block;

            padding:
                6px 10px;

            border-radius: 20px;

            background: #f3e8ff;

            color: #7c3aed;

            font-size: 12px;

            font-weight: 700;

        }


        .completed-badge {

            display: inline-block;

            padding:
                6px 10px;

            border-radius: 20px;

            background: #dcfce7;

            color: #16a34a;

            font-size: 12px;

            font-weight: 700;

        }


        .date {

            margin-top: 5px;

            color: #9ca3af;

            font-size: 11px;

        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .empty {

            padding:
                70px 25px;

            text-align: center;

            color: #9ca3af;

        }


        .empty-icon {

            font-size: 55px;

            margin-bottom: 15px;

        }


        .empty h2 {

            margin:
                0 0 8px 0;

            color: #374151;

        }


        /* =====================================================
           FOOTER
        ===================================================== */

        footer {

            text-align: center;

            padding: 30px;

            color: #9ca3af;

            font-size: 13px;

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 800px) {

            header {

                justify-content: center;

            }


            nav {

                order: 3;

                width: 100%;

                justify-content: center;

            }


            .student-info {

                margin-left: auto;

            }


            .summary {

                grid-template-columns: 1fr;

            }


            .results-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .student-label {

                text-align: left;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header>


    <a
        href=""
        class="logo"
    >

        Adaptive<span>Exam</span>

    </a>


    <nav>

        <a href="index.php">

            Dashboard

        </a>


        


        

    </nav>


    <div class="student-info">


        <span class="welcome">

            <?php

            echo htmlspecialchars(
                $student_name
            );

            ?>

        </span>


        <div class="avatar">

            <?php

            echo htmlspecialchars(
                strtoupper(
                    substr(
                        $student_name,
                        0,
                        1
                    )
                )
            );

            ?>

        </div>


        <button
            class="logout-btn"
            onclick="logout()"
        >

            Logout

        </button>


    </div>


</header>



<!-- =========================================================
     MAIN
========================================================= -->

<main>


    <div class="page-header">

        <h1>

            All Examination Results

        </h1>


        <p>

            View all examinations you have completed.

        </p>

    </div>



    <!-- =====================================================
         SUMMARY
    ===================================================== -->

    <div class="summary">


        <div class="summary-card">

            <div class="summary-label">

                Total Completed Exams

            </div>


            <div class="summary-value">

                <?php

                echo count($results);

                ?>

            </div>

        </div>



        <div class="summary-card">

            <div class="summary-label">

                Student

            </div>


            <div class="summary-value">

                <?php

                echo htmlspecialchars(
                    $student_name
                );

                ?>

            </div>

        </div>



        <div class="summary-card">

            <div class="summary-label">

                Student ID

            </div>


            <div class="summary-value">

                <?php

                echo htmlspecialchars(
                    $student_number
                );

                ?>

            </div>

        </div>


    </div>



    <!-- =====================================================
         RESULTS
    ===================================================== -->

    <section class="results-card">


        <div class="results-header">


            <div>

                <h2>

                    Completed Assessments

                </h2>


                <p>

                    Every completed assessment attempt
                    is displayed below.

                </p>

            </div>


            <div class="student-label">

                Student:

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $student_name
                    );

                    ?>

                </strong>

                <br>

                ID:

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $student_number
                    );

                    ?>

                </strong>

            </div>


        </div>



        <?php if (count($results) === 0): ?>


            <div class="empty">


                <div class="empty-icon">

                    📝

                </div>


                <h2>

                    No Completed Exams

                </h2>


                <p>

                    You have not completed any
                    examination yet.

                </p>


            </div>


        <?php else: ?>


            <div class="table-container">


                <table>


                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Examination
                            </th>

                            <th>
                                Questions
                            </th>

                            <th>
                                Correct
                            </th>

                            <th>
                                Wrong
                            </th>

                            <th>
                                Score
                            </th>

                            <th>
                                Ability
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Completed
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php

                        $counter = 1;


                        foreach (
                            $results
                            as $result
                        ):

                        ?>


                        <tr>


                            <!-- NUMBER -->

                            <td>

                                <span class="number">

                                    <?php

                                    echo $counter;

                                    ?>

                                </span>

                            </td>



                            <!-- EXAM -->

                            <td>

                                <div class="exam-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $result['exam_name']
                                    );

                                    ?>

                                </div>


                                <div class="attempt-id">

                                    Attempt #

                                    <?php

                                    echo (int)
                                        $result['attempt_id'];

                                    ?>

                                </div>

                            </td>



                            <!-- QUESTIONS -->

                            <td>

                                <?php

                                echo (int)
                                    $result[
                                        'total_questions'
                                    ];

                                ?>

                            </td>



                            <!-- CORRECT -->

                            <td>

                                <span class="correct">

                                    <?php

                                    echo (int)
                                        $result[
                                            'correct_answers'
                                        ];

                                    ?>

                                </span>

                            </td>



                            <!-- WRONG -->

                            <td>

                                <span class="wrong">

                                    <?php

                                    echo (int)
                                        $result[
                                            'wrong_answers'
                                        ];

                                    ?>

                                </span>

                            </td>



                            <!-- SCORE -->

                            <td>

                                <span class="score">

                                    <?php

                                    echo number_format(
                                        (float)
                                        $result['score'],
                                        2
                                    );

                                    ?>%

                                </span>

                            </td>



                            <!-- ABILITY -->

                            <td>

                                <span class="ability">

                                    <?php

                                    echo htmlspecialchars(
                                        $result[
                                            'ability_level'
                                        ]
                                    );

                                    ?>

                                </span>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <span
                                    class="completed-badge"
                                >

                                    ✓ Completed

                                </span>

                            </td>



                            <!-- COMPLETED -->

                            <td>

                                <?php

                                $date =
                                    strtotime(
                                        $result[
                                            'completed_at'
                                        ]
                                    );


                                if (
                                    $date !== false
                                ) {

                                    echo date(
                                        "d M Y",
                                        $date
                                    );

                                }
                                else {

                                    echo htmlspecialchars(
                                        $result[
                                            'completed_at'
                                        ]
                                    );

                                }

                                ?>


                                <?php

                                if (
                                    $date !== false
                                ):

                                ?>

                                    <div class="date">

                                        <?php

                                        echo date(
                                            "h:i A",
                                            $date
                                        );

                                        ?>

                                    </div>

                                <?php endif; ?>


                            </td>


                        </tr>


                        <?php

                        $counter++;

                        endforeach;

                        ?>


                    </tbody>


                </table>


            </div>


        <?php endif; ?>


    </section>


</main>



<footer>

    © 2026 Adaptive Online Exam System

</footer>



<script>

/* =========================================================
   LOGOUT
========================================================= */

function logout() {

    const confirmed =
        confirm(
            "Are you sure you want to logout?"
        );


    if (confirmed) {

        window.location.href =
            "../backend/auth/logout.php";

    }

}

</script>


</body>

</html>

