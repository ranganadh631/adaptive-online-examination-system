
<?php

session_start();

require_once "../backend/config/database.php";


// ============================================================
// CHECK STUDENT LOGIN
// ============================================================

if (!isset($_SESSION['student_id'])) {

    header("Location: student_login.html");
    exit();

}

$student_id = (int) $_SESSION['student_id'];


// ============================================================
// GET STUDENT DETAILS
// ============================================================

$sql = "
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

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Student query failed: " .
        $conn->error
    );

}

$stmt->bind_param(
    "i",
    $student_id
);

$stmt->execute();

$student_result = $stmt->get_result();

$student_name = "Student";
$student_number = "";
$student_email = "";
$student_mobile = "";

if ($student_result->num_rows > 0) {

    $student = $student_result->fetch_assoc();

    $student_name =
        $student['name'];

    $student_number =
        $student['student_id'];

    $student_email =
        $student['email'];

    $student_mobile =
        $student['mobile'];

} else {

    session_destroy();

    header(
        "Location: student_login.html"
    );

    exit();

}

$stmt->close();


// ============================================================
// GET ACTIVE EXAMS
// ============================================================

$exam_sql = "
    SELECT
        id,
        exam_name,
        description,
        duration,
        total_questions,
        status
    FROM exams
    WHERE status = 'Active'
    ORDER BY id DESC
";

$exam_result =
    $conn->query($exam_sql);

if (!$exam_result) {

    die(
        "Exam query failed: " .
        $conn->error
    );

}

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
        Student Dashboard - Adaptive Online Examination System
    </title>


    <!-- BOOTSTRAP -->

    <link
        href="assets/vendor/bootstrap/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP ICONS -->

    <link
        href="assets/vendor/bootstrap-icons/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background: #f5f7fa;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: #212529;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .header {

            background: #ffffff;

            padding:
                18px 40px;

            box-shadow:
                0 2px 10px
                rgba(0,0,0,0.08);

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .logo {

            text-decoration: none;

            color: #222;

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .logo i {

            font-size: 32px;

            color: #0d6efd;
        }


        .logo h2 {

            margin: 0;

            font-size: 22px;

            font-weight: 700;
        }


        .logout-btn {

            text-decoration: none;

            padding:
                9px 18px;

            border-radius: 8px;

            color: white;

            background: #dc3545;

            font-weight: 600;
        }


        .logout-btn:hover {

            background: #bb2d3b;

            color: white;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .dashboard-container {

            max-width: 1400px;

            margin: 0 auto;

            padding: 40px 30px;
        }


        /* =====================================================
           WELCOME
        ===================================================== */

        .welcome-card {

            background:
                linear-gradient(
                    135deg,
                    #0d6efd,
                    #0b5ed7
                );

            color: white;

            border-radius: 18px;

            padding: 30px;

            margin-bottom: 30px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.10);
        }


        .welcome-card h1 {

            margin:
                0 0 8px 0;

            font-size: 30px;
        }


        .welcome-card p {

            margin: 0;

            font-size: 16px;
        }


        /* =====================================================
           PROFILE CARD
        ===================================================== */

        .profile-card {

            background: white;

            border-radius: 18px;

            padding: 25px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.08);

            height: 100%;
        }


        .profile-icon {

            font-size: 70px;

            color: #0d6efd;
        }


        .profile-info {

            margin-top: 25px;
        }


        .profile-info p {

            margin-bottom: 15px;

            word-break: break-word;
        }


        .profile-info i {

            color: #0d6efd;

            width: 25px;

            margin-right: 5px;
        }


        /* =====================================================
           EXAMS
        ===================================================== */

        .exam-card {

            background: white;

            border-radius: 18px;

            padding: 25px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.08);

            height: 100%;

            transition:
                transform 0.2s,
                box-shadow 0.2s;
        }


        .exam-card:hover {

            transform:
                translateY(-4px);

            box-shadow:
                0 8px 25px
                rgba(0,0,0,0.12);
        }


        .exam-icon {

            font-size: 50px;

            color: #198754;
        }


        .exam-card h4 {

            font-size: 20px;

            line-height: 1.4;
        }


        .exam-details {

            background: #f8f9fa;

            border-radius: 10px;

            padding: 15px;

            margin-top: 15px;
        }


        .exam-details span {

            font-size: 14px;

            color: #495057;
        }


        .start-btn {

            width: 100%;

            padding: 11px;

            font-weight: 600;

            border-radius: 8px;
        }


        /* =====================================================
           NO EXAM
        ===================================================== */

        .no-exam {

            background: white;

            border-radius: 18px;

            padding: 50px 30px;

            text-align: center;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.08);
        }


        .no-exam i {

            font-size: 55px;

            color: #ffc107;
        }


        /* =====================================================
           RESULT CARD
        ===================================================== */

        .result-card {

            background: white;

            border-radius: 18px;

            padding: 25px;

            margin-top: 30px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.08);
        }


        .result-card h3 {

            margin-bottom: 8px;
        }


        .result-card .result-icon {

            font-size: 24px;

            color: #0d6efd;
        }


        .result-description {

            color: #6c757d;

            margin-bottom: 0;
        }


        .view-results-btn {

            min-width: 160px;

            font-weight: 600;

            padding:
                11px 20px;

            border-radius: 8px;
        }


        /* =====================================================
           RESULT INFO
        ===================================================== */

        .result-info {

            background: #f8f9fa;

            border-radius: 10px;

            padding: 15px;

            margin-top: 15px;

            color: #6c757d;

            font-size: 14px;
        }


        .result-info i {

            color: #0d6efd;

            margin-right: 6px;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .header {

                padding:
                    15px 20px;
            }


            .logo h2 {

                font-size: 16px;
            }


            .logo i {

                font-size: 25px;
            }


            .logout-btn {

                padding:
                    7px 12px;

                font-size: 13px;
            }


            .dashboard-container {

                padding:
                    20px 15px;
            }


            .welcome-card {

                padding:
                    25px 20px;
            }


            .welcome-card h1 {

                font-size: 24px;
            }


            .result-card
            .d-flex {

                align-items: flex-start !important;
            }


            .view-results-btn {

                width: 100%;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="header">


    <a
        href="index.php"
        class="logo"
    >

        <i class="bi bi-mortarboard-fill"></i>

        <h2>
            Adaptive Online Examination System
        </h2>

    </a>


    <a
        href="../backend/auth/logout.php"
        class="logout-btn"
    >

        <i class="bi bi-box-arrow-right"></i>

        Logout

    </a>


</header>



<!-- =========================================================
     MAIN
========================================================= -->

<div class="dashboard-container">


    <!-- =====================================================
         WELCOME
    ===================================================== -->

    <div class="welcome-card">

        <h1>

            Welcome,
            <?php
            echo htmlspecialchars(
                $student_name
            );
            ?>
            👋

        </h1>


        <p>

            Welcome to your Dashboard.
            You can start your examination
            

        </p>

    </div>



    <!-- =====================================================
         PROFILE + EXAMS
    ===================================================== -->

    <div class="row g-4">


        <!-- =================================================
             PROFILE
        ================================================= -->

        <div class="col-lg-4">

            <div class="profile-card">


                <div class="text-center">

                    <i
                        class="bi bi-person-circle profile-icon"
                    ></i>


                    <h3 class="mt-2">

                        Student Profile

                    </h3>

                </div>



                <div class="profile-info">


                    <!-- NAME -->

                    <p>

                        <i
                            class="bi bi-person-fill"
                        ></i>

                        <strong>
                            Name:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $student_name
                        );

                        ?>

                    </p>



                    <!-- STUDENT ID -->

                    <p>

                        <i
                            class="bi bi-card-text"
                        ></i>

                        <strong>
                            Student ID:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $student_number
                        );

                        ?>

                    </p>



                    <!-- EMAIL -->

                    <p>

                        <i
                            class="bi bi-envelope-fill"
                        ></i>

                        <strong>
                            Email:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $student_email
                        );

                        ?>

                    </p>



                    <!-- MOBILE -->

                    <p>

                        <i
                            class="bi bi-phone-fill"
                        ></i>

                        <strong>
                            Mobile:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $student_mobile
                        );

                        ?>

                    </p>


                </div>

            </div>

        </div>



        <!-- =================================================
             EXAMS
        ================================================= -->

        <div class="col-lg-8">


            <h3 class="mb-4">

                <i
                    class="bi bi-journal-check text-primary"
                ></i>

                Available Examinations

            </h3>



            <div class="row g-4">


                <?php

                if (
                    $exam_result->num_rows > 0
                ) {

                    while (
                        $exam =
                        $exam_result->fetch_assoc()
                    ) {

                        $exam_id =
                            (int) $exam['id'];

                ?>


                <!-- =========================================
                     EXAM CARD
                ========================================== -->

                <div class="col-md-6">


                    <div class="exam-card">


                        <div class="text-center">


                            <i
                                class="
                                bi
                                bi-file-earmark-text
                                exam-icon
                                "
                            ></i>


                            <h4 class="mt-3">

                                <?php

                                echo htmlspecialchars(
                                    $exam['exam_name']
                                );

                                ?>

                            </h4>


                        </div>



                        <hr>



                        <!-- DESCRIPTION -->

                        <p class="text-muted">

                            <?php

                            if (
                                !empty(
                                    $exam['description']
                                )
                            ) {

                                echo htmlspecialchars(
                                    $exam['description']
                                );

                            } else {

                                echo
                                    "Test your knowledge with this examination.";

                            }

                            ?>

                        </p>



                        <!-- EXAM DETAILS -->

                        <div class="exam-details">


                            <!-- QUESTIONS -->

                            <div
                                class="
                                d-flex
                                justify-content-between
                                mb-2
                                "
                            >

                                <span>

                                    <i
                                        class="
                                        bi
                                        bi-question-circle
                                        "
                                    ></i>

                                    Questions

                                </span>


                                <strong>

                                    <?php

                                    echo (int)
                                        $exam[
                                            'total_questions'
                                        ];

                                    ?>

                                </strong>

                            </div>



                            <!-- DURATION -->

                            <div
                                class="
                                d-flex
                                justify-content-between
                                "
                            >

                                <span>

                                    <i
                                        class="
                                        bi
                                        bi-clock
                                        "
                                    ></i>

                                    Duration

                                </span>


                                <strong>

                                    <?php

                                    echo (int)
                                        $exam[
                                            'duration'
                                        ];

                                    ?>

                                    Minutes

                                </strong>

                            </div>


                        </div>



                        <!-- START EXAM -->

                        <a
                            href="exam.php?exam_id=<?php echo $exam_id; ?>"
                            class="
                                btn
                                btn-success
                                start-btn
                                mt-3
                            "
                        >

                            <i
                                class="
                                bi
                                bi-play-circle-fill
                                "
                            ></i>

                            Start Examination

                        </a>


                    </div>


                </div>


                <?php

                    }

                } else {

                ?>


                <!-- =========================================
                     NO EXAMS
                ========================================== -->

                <div class="col-12">


                    <div class="no-exam">


                        <i
                            class="
                            bi
                            bi-exclamation-circle
                            "
                        ></i>


                        <h4 class="mt-3">

                            No Active Examinations

                        </h4>


                        <p class="text-muted">

                            There are currently no examinations
                            available. Please check again later.

                        </p>


                    </div>


                </div>


                <?php

                }

                ?>


            </div>

        </div>

    </div>



    <!-- =====================================================
         ALL EXAMINATION RESULTS
    ===================================================== -->

    <div class="result-card">


        <div
            class="
            d-flex
            justify-content-between
            align-items-center
            flex-wrap
            gap-3
            "
        >


            <div>


                <h3>

                    <i
                        class="
                        bi
                        bi-bar-chart-fill
                        result-icon
                        "
                    ></i>

                    Examination Results

                </h3>


                <p class="result-description">

                    View all of your completed examination
                    attempts, including your score, correct
                    answers, wrong answers and ability level.

                </p>


            </div>



            <!-- =================================================
                 VIEW ALL RESULTS
            ================================================== -->

            <a
                href="student-result.html"
                class="
                    btn
                    btn-primary
                    view-results-btn
                "
            >

                <i
                    class="
                    bi
                    bi-eye-fill
                    "
                ></i>

                View All Results

            </a>


        </div>


        <hr>


        <div class="result-info">

            <i class="bi bi-info-circle-fill"></i>

            Every completed examination will appear on the
            results page. If you complete multiple exams,
            all completed attempts will be displayed there,
            with the newest result shown first.

        </div>


    </div>


</div>



<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"
></script>


</body>

</html>

