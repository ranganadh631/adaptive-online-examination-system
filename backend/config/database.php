<?php

$host = "sql111.ezyro.com";
$user = "ezyro_42767668";
$password = "eee9e5b8";
$database = "ezyro_42767668_badri";



$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {

    die(
        "Database connection failed: " .
        $conn->connect_error
    );

}

$conn->set_charset("utf8mb4");

?>