<?php
session_start();
require_once __DIR__ . "/config.php";
require_admin();

$sql = "SELECT
            p.receipt_number,
            p.payment_month,
            p.payment_year,
            p.payment_date,
            p.amount,
            p.notes,
            c.customer_code,
            c.customer_name,
            c.mobile
        FROM rd_payments p
        JOIN customers c ON c.id = p.customer_id";

$params = [];
$types = "";

if (!empty($_GET["customer"])) {
    $sql .= " WHERE (
        c.customer_name LIKE ?
        OR c.mobile LIKE ?
        OR c.customer_code LIKE ?
    )";

    $q = "%" . $_GET["customer"] . "%";
    $params = [$q, $q, $q];
    $types = "sss";
}

$sql .= " ORDER BY p.payment_year DESC, p.payment_month DESC, p.id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$rows = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Payment History</title>

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
    max-width: 1100px;
    margin: auto;
}
input, button {
    padding: 10px;
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
    padding: 8px;
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
}
</style>
</head>

<body>
<main>
<h2>RD Payment History</h2>

<form method="get">
    <input
        name="customer"
        placeholder="Search name, mobile or ID"
        value="<?= htmlspecialchars($_GET["customer"] ?? "") ?>"
    >
    <button type="submit">Search</button>
    <button type="button" onclick="window.print()">Print</button>
</form>

<div class="wrap">
<table>
<thead>
<tr>
    <th>Receipt</th>
    <th>Customer ID</th>
    <th>Name</th>
    <th>Mobile</th>
    <th>Month</th>
    <th>Paid Date</th>
    <th>Amount</th>
    <th>Note</th>
</tr>
</thead>

<tbody>
<?php if ($rows->num_rows > 0): ?>
    <?php while ($r = $rows->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($r["receipt_number"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["customer_code"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["customer_name"] ?? "") ?></td>
        <td><?= htmlspecialchars($r["mobile"] ?? "") ?></td>
        <td>
            <?= htmlspecialchars(
                date(
                    "M Y",
                    mktime(0, 0, 0, (int)$r["payment_month"], 1, (int)$r["payment_year"])
                )
            ) ?>
        </td>
        <td><?= htmlspecialchars($r["payment_date"] ?? "") ?></td>
        <td>₹<?= number_format((float)$r["amount"], 2) ?></td>
        <td><?= htmlspecialchars($r["notes"] ?? "") ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="8">No payment history found.</td>
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