<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli("localhost", "root", "", "jalaram_rd");
$conn->set_charset("utf8mb4");

function require_admin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION["admin_id"])) {
        header("Location: login.php");
        exit;
    }
}
?>