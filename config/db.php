<?php
function getDbConnection() {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'verify_app';

    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    return $conn;
}
?>
