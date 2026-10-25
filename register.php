<?php
session_start();
require_once __DIR__ . "/config.php";
require_admin();

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["customer_name"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $amount = (float)($_POST["monthly_amount"] ?? 0);
    $start = $_POST["start_date"] ?? "";

    if ($name === "" || $mobile === "" || $amount <= 0 || $start === "") {
        $error = "Please fill all required fields correctly.";
    } else {
        try {
            $startDate = DateTimeImmutable::createFromFormat("!Y-m-d", $start);

            if (
                !$startDate ||
                $startDate->format("Y-m-d") !== $start
            ) {
                throw new Exception("Please enter a valid RD start date.");
            }

            // Calculate maturity date after 5 years
            $maturity = $startDate
                ->modify("+5 years")
                ->format("Y-m-d");

            $code = "JRD" . date("ymd") .
                strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $stmt = $conn->prepare(
                "INSERT INTO customers
                (customer_code, customer_name, mobile, address,
                 monthly_amount, start_date, maturity_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssdss",
                $code,
                $name,
                $mobile,
                $address,
                $amount,
                $start,
                $maturity
            );

            $stmt->execute();
            $stmt->close();

            $message = "Customer registered successfully! Customer Code: "
                . $code
                . " | Maturity Date: "
                . date("d-m-Y", strtotime($maturity));

        } catch (Throwable $e) {
            $error = "Customer save failed: " . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add RD Customer</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    padding: 20px;
}
.card {
    background: white;
    max-width: 650px;
    margin: auto;
    padding: 22px;
    border-radius: 12px;
}
input, textarea, button {
    width: 100%;
    padding: 11px;
    margin: 7px 0;
    box-sizing: border-box;
}
button {
    background: #123b70;
    color: white;
    border: 0;
    border-radius: 7px;
    cursor: pointer;
}
.ok {
    color: green;
    overflow-wrap: anywhere;
}
.err {
    color: #b91c1c;
    overflow-wrap: anywhere;
}
</style>
</head>

<body>
<div class="card">
    <h2>Add RD Customer</h2>

    <?php if ($message !== ""): ?>
        <p class="ok"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <p class="err"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Full name *</label>
        <input name="customer_name" required>

        <label>Mobile number *</label>
        <input name="mobile" type="tel" required>

        <label>Address</label>
        <textarea name="address"></textarea>

        <label>Monthly RD amount (₹) *</label>
        <input type="number" name="monthly_amount"
               min="1" step="0.01" required>

        <label>RD start date *</label>
        <input type="date" name="start_date" required>

        <label>RD maturity date (5 years)</label>
        <input type="text" id="maturity_date"
               placeholder="Automatically calculated"
               readonly>

        <button type="submit">Save Customer</button>
    </form>

    <p><a href="dashboard.php">← Dashboard</a></p>
</div>

<script>
const startInput = document.querySelector('[name="start_date"]');
const maturityInput = document.getElementById('maturity_date');

startInput.addEventListener('change', function () {
    if (!this.value) {
        maturityInput.value = "";
        return;
    }

    const parts = this.value.split("-").map(Number);
    const year = parts[0] + 5;
    const month = parts[1];
    const day = parts[2];

    // Keep the same day when possible (including leap dates)
    const lastDay = new Date(Date.UTC(year, month, 0)).getUTCDate();
    const finalDay = Math.min(day, lastDay);

    maturityInput.value =
        String(finalDay).padStart(2, "0") + "-" +
        String(month).padStart(2, "0") + "-" + year;
});
</script>

</body>
</html>