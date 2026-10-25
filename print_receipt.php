<?php
session_start();
require_once __DIR__ . "/config.php";
require_admin();

$receipt = trim($_GET["receipt"] ?? "");
$row = null;

if ($receipt !== "") {
    $stmt = $conn->prepare("
        SELECT
            p.*,
            c.customer_code,
            c.customer_name,
            c.mobile,
            c.address
        FROM rd_payments p
        JOIN customers c ON c.id = p.customer_id
        WHERE p.receipt_number = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $receipt);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$recent = $conn->query("
    SELECT
        p.receipt_number,
        p.payment_date,
        c.customer_name
    FROM rd_payments p
    JOIN customers c ON c.id = p.customer_id
    ORDER BY p.id DESC
    LIMIT 30
");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Print RD Receipt</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    padding: 18px;
}
main {
    background: white;
    padding: 22px;
    border-radius: 12px;
    max-width: 700px;
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
.receipt {
    border: 1px dashed #64748b;
    padding: 20px;
    margin-top: 20px;
}
dt {
    font-weight: bold;
    margin-top: 10px;
}
dd {
    margin: 3px 0;
}
@media print {
    body {
        background: white;
        padding: 0;
    }
    main {
        max-width: 100%;
    }
    form, .recent, .back-link, .print-button {
        display: none;
    }
    .receipt {
        border: 1px solid #333;
    }
}
</style>
</head>

<body>
<main>
<h2>Print / PDF Receipt</h2>

<form method="get">
    <label>Receipt Number</label>
    <input
        name="receipt"
        value="<?= htmlspecialchars($receipt) ?>"
        required
    >
    <button type="submit">Find Receipt</button>
</form>

<?php if ($row): ?>
<section class="receipt">
    <h2>🏦 Jalaram Investment</h2>
    <h3>RD Payment Receipt</h3>

    <dl>
        <dt>Receipt No.</dt>
        <dd><?= htmlspecialchars($row["receipt_number"] ?? "") ?></dd>

        <dt>Customer Name</dt>
        <dd><?= htmlspecialchars($row["customer_name"] ?? "") ?></dd>

        <dt>Customer ID</dt>
        <dd><?= htmlspecialchars($row["customer_code"] ?? "") ?></dd>

        <dt>Mobile</dt>
        <dd><?= htmlspecialchars($row["mobile"] ?? "") ?></dd>

        <dt>Address</dt>
        <dd><?= nl2br(htmlspecialchars($row["address"] ?? "")) ?></dd>

        <dt>Payment Month</dt>
        <dd>
        <?php
        echo htmlspecialchars(
            date(
                "F Y",
                mktime(
                    0, 0, 0,
                    (int)$row["payment_month"],
                    1,
                    (int)$row["payment_year"]
                )
            )
        );
        ?>
        </dd>

        <dt>Payment Date</dt>
        <dd><?= htmlspecialchars($row["payment_date"] ?? "") ?></dd>

        <dt>Amount Paid</dt>
        <dd>₹<?= number_format((float)$row["amount"], 2) ?></dd>
    </dl>

    <button class="print-button" onclick="window.print()">
        Print / Save PDF
    </button>
</section>

<?php elseif ($receipt !== ""): ?>
    <p>Receipt not found. Please check the receipt number.</p>
<?php endif; ?>

<div class="recent">
<h3>Recent Receipts</h3>
<ul>
<?php while ($r = $recent->fetch_assoc()): ?>
    <li>
        <a href="?receipt=<?= urlencode($r["receipt_number"]) ?>">
            <?= htmlspecialchars(
                $r["receipt_number"] . " — " .
                $r["customer_name"] . " — " .
                $r["payment_date"]
            ) ?>
        </a>
    </li>
<?php endwhile; ?>
</ul>
</div>

<p class="back-link">
    <a href="dashboard.php">← Dashboard</a>
</p>
</main>
</body>
</html>