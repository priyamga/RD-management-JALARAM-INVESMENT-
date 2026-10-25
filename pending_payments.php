<?php
session_start();
require_once __DIR__ . "/config.php";
require_admin();

$month = $_GET["month"] ?? date("Y-m");
$timestamp = strtotime($month . "-01");

if ($timestamp === false) {
    $month = date("Y-m");
    $timestamp = strtotime($month . "-01");
}

$monthDate = date("Y-m-01", $timestamp);
$monthNumber = (int) date("n", $timestamp);
$yearNumber = (int) date("Y", $timestamp);

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.customer_code,
        c.customer_name,
        c.mobile,
        c.monthly_amount
    FROM customers c
    WHERE c.start_date <= LAST_DAY(?)
    AND NOT EXISTS (
        SELECT 1
        FROM rd_payments p
        WHERE p.customer_id = c.id
          AND p.payment_month = ?
          AND p.payment_year = ?
    )
    ORDER BY c.customer_name
");

$stmt->bind_param("sii", $monthDate, $monthNumber, $yearNumber);
$stmt->execute();
$rows = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pending RD Payments</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    padding: 18px;
}
main {
    background: white;
    padding: 20px;
    border-radius: 12px;
    max-width: 1000px;
    margin: auto;
}
input, button {
    padding: 9px;
    margin: 4px;
}
button {
    background: #123b70;
    color: white;
    border: 0;
    border-radius: 5px;
    cursor: pointer;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
td, th {
    border: 1px solid #ddd;
    padding: 9px;
    text-align: left;
}
th {
    background: #123b70;
    color: white;
}
.wrap {
    overflow-x: auto;
}
@media print {
    form, .back-link {
        display: none;
    }
    body {
        background: white;
        padding: 0;
    }
    main {
        box-shadow: none;
    }
}
</style>
</head>

<body>
<main>
<h2>Pending RD Payments</h2>

<form method="get">
    <label>Month: </label>
    <input
        type="month"
        name="month"
        value="<?= htmlspecialchars($month) ?>"
        required
    >
    <button type="submit">Show</button>
    <button type="button" onclick="window.print()">Print List</button>
</form>

<div class="wrap">
<table>
<thead>
<tr>
    <th>Customer ID</th>
    <th>Name</th>
    <th>Mobile</th>
    <th>Monthly Amount</th>
</tr>
</thead>

<tbody>
<?php if ($rows->num_rows > 0): ?>
    <?php while ($r = $rows->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($r["customer_code"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["customer_name"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["mobile"] ?? "") ?></td>
        <td>₹<?= number_format((float)$r["monthly_amount"], 2) ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="4">No pending payments found for this month.</td>
    </tr>
<?php endif; ?>
</tbody>
</table>
</div>

<p class="back-link">
    <a href="dashboard.php">← Dashboard</a>
</p>
</main>
</body>
</html>