<?php
session_start();
require_once __DIR__ . "/config.php";
require_admin();

// RD maturity is calculated as 5 years after start_date.
// Show accounts maturing from today through the next month.
$sql = "
    SELECT
        customer_name,
        mobile,
        rd_account_number,
        start_date,
        DATE_ADD(start_date, INTERVAL 5 YEAR) AS maturity_date
    FROM customers
    WHERE DATE_ADD(start_date, INTERVAL 5 YEAR)
          BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
    ORDER BY DATE_ADD(start_date, INTERVAL 5 YEAR) ASC
";

$result = $conn->query($sql);
$total = $result->num_rows;

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function showDate($date) {
    return $date ? date("d-m-Y", strtotime($date)) : "-";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RD Close Account List | Jalaram Investment</title>

<style>
* {
    box-sizing: border-box;
}
body {
    margin: 0;
    padding: 20px;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #172554;
}
.container {
    max-width: 1200px;
    margin: auto;
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 3px 15px #00000012;
}
.header {
    text-align: center;
    border-bottom: 2px solid #123b70;
    padding-bottom: 14px;
    margin-bottom: 20px;
}
.header h1 {
    color: #123b70;
    margin: 0 0 8px;
}
.summary {
    background: #eff6ff;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 18px;
}
.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 18px;
}
.btn {
    display: inline-block;
    padding: 11px 16px;
    border: 0;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
}
.blue {
    background: #123b70;
}
.green {
    background: #15803d;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    border: 1px solid #cbd5e1;
    padding: 11px 9px;
    text-align: left;
    font-size: 14px;
}
th {
    background: #123b70;
    color: white;
}
.empty {
    text-align: center;
    padding: 25px;
    color: #64748b;
}
.footer {
    margin-top: 20px;
    font-size: 12px;
    color: #64748b;
    text-align: center;
}
@media (max-width: 700px) {
    body {
        padding: 8px;
    }
    .container {
        padding: 12px;
        overflow-x: auto;
    }
    th, td {
        padding: 8px 6px;
        font-size: 12px;
        white-space: nowrap;
    }
}
@media print {
    body {
        padding: 0;
        background: white;
    }
    .container {
        max-width: none;
        padding: 0;
        box-shadow: none;
    }
    .no-print {
        display: none !important;
    }
    th {
        background: #ddd !important;
        color: black !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    table {
        page-break-inside: auto;
    }
    tr {
        page-break-inside: avoid;
    }
}
</style>
</head>

<body>
<div class="container">

    <div class="header">
        <h1>Jalaram Investment</h1>
        <div>Post Office RD Management System</div>
        <h2>RD Close Account List</h2>
        <div>Accounts maturing in the next 1 month</div>
    </div>

    <div class="summary">
        <strong>Total accounts maturing:</strong>
        <?= (int)$total ?>
        <br>
        <strong>List date:</strong>
        <?= date("d-m-Y") ?>
        to
        <?= date("d-m-Y", strtotime("+1 month")) ?>
    </div>

    <div class="actions no-print">
        <button class="btn green" onclick="window.print()">
            Print / Save PDF
        </button>
        <a class="btn blue" href="dashboard.php">
            Back to Dashboard
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Customer Name</th>
                <th>Mobile Number</th>
                <th>RD Account Number</th>
                <th>Start Date</th>
                <th>Maturity Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($total > 0): ?>
            <?php $i = 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= h($row["customer_name"]) ?></td>
                    <td><?= h($row["mobile"]) ?></td>
                    <td><?= h($row["rd_account_number"]) ?></td>
                    <td><?= h(showDate($row["start_date"])) ?></td>
                    <td><?= h(showDate($row["maturity_date"])) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="empty">
                    No RD accounts are maturing in the next month.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Jalaram Investment | RD Close Account Report
    </div>

</div>
</body>
</html>