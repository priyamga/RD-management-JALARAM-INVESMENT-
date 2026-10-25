<?php
session_start();
require_once __DIR__ . "/config.php";
require_admin();

$adminName = $_SESSION["admin_username"] ?? "Admin";

$customerCount = (int)$conn->query(
    "SELECT COUNT(*) n FROM customers"
)->fetch_assoc()["n"];

$paymentCount = (int)$conn->query(
    "SELECT COUNT(*) n FROM rd_payments"
)->fetch_assoc()["n"];

$paidTotal = $conn->query(
    "SELECT COALESCE(SUM(amount),0) n FROM rd_payments
     WHERE payment_status = 'Paid'"
)->fetch_assoc()["n"];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Jalaram Investment Dashboard</title>

<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:#f1f5f9;color:#172554}
header{background:#123b70;color:white;padding:22px}
main{max-width:1050px;margin:auto;padding:22px 15px}
.stats,.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:14px}
.card,.menu{background:white;padding:20px;border-radius:12px;border:1px solid #dbeafe}
.menu{display:block;text-decoration:none;color:#123b70;text-align:center;font-weight:bold;transition:.2s}
.menu:hover{background:#dbeafe;transform:translateY(-2px)}
.advance{
    background:white;
    border:1px solid #dbeafe;
    color:#123b70;
}
.advance:hover{
    background:#dbeafe;
    transform:translateY(-2px);
}
.symbol{font-size:30px;display:block;margin-bottom:10px}
.logout{display:inline-block;margin-top:20px;padding:12px 20px;background:#dc2626;color:white;border-radius:7px;text-decoration:none}
</style>
</head>

<body>

<header>
<h2>🏦 Jalaram Investment</h2>
<p>Post Office RD Management System</p>
</header>

<main>

<section class="card">
<h3>Welcome, <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>!</h3>
<p>Admin Dashboard</p>
</section>

<br>

<section class="stats">
<div class="card">Customers: <strong><?= $customerCount ?></strong></div>
<div class="card">Payments: <strong><?= $paymentCount ?></strong></div>
<div class="card">Total collected: <strong>₹<?= number_format((float)$paidTotal,2) ?></strong></div>
</section>

<br>

<section class="grid">

<a class="menu" href="register.php">
<span class="symbol">👤</span>
Add Customer
</a>

<a class="menu" href="rd_payment.php">
<span class="symbol">💰</span>
RD Payment
</a>

<a class="menu advance" href="advance_payment.php">
<span class="symbol">🗓️</span>
Advance Payment
<br><small>12 Months • 3.3% Rebate</small>
</a>

<a class="menu" href="pending_payments.php">
<span class="symbol">📅</span>
Pending Payments
</a>

<a class="menu" href="payment_history.php">
<span class="symbol">📋</span>
Payment History
</a>

<a class="menu" href="reports.php">
<span class="symbol">📊</span>
Reports
</a>

<a class="menu" href="rd_close_accounts.php">
    <span class="symbol">📅</span>
    RD Close Account List
</a>

<a class="menu" href="print_receipt.php">
<span class="symbol">🖨️</span>
Print / PDF
</a>

</section>

<a class="logout" href="logout.php">Logout</a>

</main>
</body>
</html>