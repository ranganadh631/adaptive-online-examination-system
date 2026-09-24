<?php



/* ============================================================
   backend/auth/register.php
   STUDENT REGISTRATION
   ============================================================ */

session_start();

/*
 * Do NOT hide MySQL errors while testing.
 * This allows us to see the real problem.
 */
mysqli_report(MYSQLI_REPORT_OFF);


/* ============================================================
   DATABASE
============================================================ */

require_once "../config/database.php";


/* ============================================================
   CHECK DATABASE CONNECTION
============================================================ */

if (!$conn) {

    die(
        "Database connection failed."
    );
}

if ($conn->connect_errno) {

    die(
        "Database connection failed: " .
        $conn->connect_error
    );
}


/* ============================================================
   ONLY POST ALLOWED
============================================================ */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    die("Invalid request method.");
}


/* ============================================================
   GET FORM DATA
============================================================ */

$name = trim(
    $_POST['name'] ?? ''
);

$student_id = trim(
    $_POST['student_id'] ?? ''
);

$email = trim(
    $_POST['email'] ?? ''
);

$mobile = trim(
    $_POST['mobile'] ?? ''
);

$password = $_POST['password'] ?? '';

$confirm_password =
    $_POST['confirm_password'] ?? '';


/* ============================================================
   VALIDATE REQUIRED FIELDS
============================================================ */

if (
    $name === '' ||
    $student_id === '' ||
    $email === '' ||
    $mobile === '' ||
    $password === '' ||
    $confirm_password === ''
) {

    die(
        "All fields are required."
    );
}


/* ============================================================
   CHECK PASSWORD
============================================================ */

if ($password !== $confirm_password) {

    die(
        "Passwords do not match."
    );
}


/* ============================================================
   CHECK EMAIL
============================================================ */

if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    die(
        "Invalid email address."
    );
}


/* ============================================================
   CHECK STUDENT ID
============================================================ */

$sql = "
    SELECT id
    FROM students
    WHERE student_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Student ID check failed: " .
        $conn->error
    );
}

$stmt->bind_param(
    "s",
    $student_id
);

if (!$stmt->execute()) {

    die(
        "Student ID check failed: " .
        $stmt->error
    );
}

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $stmt->close();

    die(
        "Student ID already registered."
    );
}

$stmt->close();


/* ============================================================
   CHECK EMAIL
============================================================ */

$sql = "
    SELECT id
    FROM students
    WHERE email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Email check failed: " .
        $conn->error
    );
}

$stmt->bind_param(
    "s",
    $email
);

if (!$stmt->execute()) {

    die(
        "Email check failed: " .
        $stmt->error
    );
}

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $stmt->close();

    die(
        "Email already registered."
    );
}

$stmt->close();


/* ============================================================
   HASH PASSWORD
============================================================ */

$hashed_password = password_hash(
    $password,
    PASSWORD_DEFAULT
);

if ($hashed_password === false) {

    die(
        "Unable to secure password."
    );
}


/* ============================================================
   INSERT STUDENT
============================================================ */

$sql = "
    INSERT INTO students
    (
        name,
        student_id,
        email,
        mobile,
        password
    )
    VALUES
    (?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Registration query preparation failed: " .
        $conn->error
    );
}


/* ============================================================
   BIND DATA
============================================================ */

$stmt->bind_param(
    "sssss",
    $name,
    $student_id,
    $email,
    $mobile,
    $hashed_password
);


/* ============================================================
   INSERT
============================================================ */

if (!$stmt->execute()) {

    $error =
        $stmt->error;

    $stmt->close();

    $conn->close();

    die(
        "Registration failed.<br><br>" .
        "Database error: " .
        htmlspecialchars($error)
    );
}


/* ============================================================
   SUCCESS
============================================================ */

$new_student_id =
    $conn->insert_id;


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
        Registration Successful
    </title>

    <style>

        body {

            margin: 0;

            font-family:
                Arial,
                sans-serif;

            background:
                #f5f7fa;

            display:
                flex;

            justify-content:
                center;

            align-items:
                center;

            min-height:
                100vh;

        }

        .card {

            background:
                white;

            width:
                90%;

            max-width:
                500px;

            padding:
                40px;

            border-radius:
                15px;

            box-shadow:
                0 5px 25px
                rgba(0,0,0,0.10);

            text-align:
                center;

        }

        .success {

            font-size:
                60px;

            color:
                #198754;

        }

        h2 {

            color:
                #198754;

        }

        .btn {

            display:
                inline-block;

            margin-top:
                20px;

            padding:
                12px 25px;

            background:
                #0d6efd;

            color:
                white;

            text-decoration:
                none;

            border-radius:
                8px;

            font-weight:
                bold;

        }

    </style>

</head>

<body>

<div class="card">

    <div class="success">
        ✓
    </div>

    <h2>
        Registration Successful!
    </h2>

    <p>

        Welcome,
        <strong>
            <?php
            echo htmlspecialchars($name);
            ?>
        </strong>

    </p>

    <p>

        Your student account has been created successfully.

    </p>

    <p>

        Student ID:
        <strong>
            <?php
            echo htmlspecialchars($student_id);
            ?>
        </strong>

    </p>

    <a
        href="../../frontend/login.html"
        class="btn"
    >
        Go to Student Login
    </a>

</div>

</body>

</html>