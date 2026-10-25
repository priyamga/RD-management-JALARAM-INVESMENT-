<?php
session_start();
require_once "config.php";
require_admin();

$message = "";
$error = "";
$receipt = "";

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$customers = [];
$result = $conn->query(
    "SELECT id, customer_name, mobile, customer_code,
            monthly_amount
     FROM customers
     ORDER BY customer_name ASC"
);

while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (!hash_equals(
            $_SESSION["csrf_token"],
            $_POST["csrf_token"] ?? ""
        )) {
            throw new Exception("Invalid request. Please reload the page.");
        }

        $customerId = (int)($_POST["customer_id"] ?? 0);
        $startMonth = (int)($_POST["start_month"] ?? 0);
        $startYear = (int)($_POST["start_year"] ?? 0);
        $paymentDate = $_POST["payment_date"] ?? date("Y-m-d");

        if ($customerId <= 0 || $startMonth < 1 || $startMonth > 12) {
            throw new Exception("Customer ane start month select karo.");
        }

        if ($startYear < 2020 || $startYear > 2100) {
            throw new Exception("Year sachu select karo.");
        }

        $dateObj = DateTime::createFromFormat("!Y-m-d", $paymentDate);
        if (!$dateObj || $dateObj->format("Y-m-d") !== $paymentDate) {
            throw new Exception("Payment date sachi nathi.");
        }

        $stmt = $conn->prepare(
            "SELECT customer_name, mobile, customer_code, monthly_amount
             FROM customers WHERE id = ?"
        );
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$customer) {
            throw new Exception("Customer malyo nathi.");
        }

        $monthlyAmount = (float)$customer["monthly_amount"];
        $grossAmount = round($monthlyAmount * 12, 2);

        // Calculate 3.3% rebate on 12-month total
        $rebatePercent = 3.3;
        $rebateAmount = round($grossAmount * $rebatePercent / 100, 2);
        $netAmount = round($grossAmount - $rebateAmount, 2);

        if ($monthlyAmount <= 0 || $netAmount <= 0) {
            throw new Exception("Payment amount sachi nathi.");
        }

        // Calculate all 12 months, including year changes
        $months = [];
        $start = DateTime::createFromFormat(
            "!Y-n-j",
            $startYear . "-" . $startMonth . "-1"
        );

        for ($i = 0; $i < 12; $i++) {
            $monthDate = clone $start;
            $monthDate->modify("+$i months");

            $months[] = [
                "month" => (int)$monthDate->format("n"),
                "year" => (int)$monthDate->format("Y")
            ];
        }

        $conn->begin_transaction();

        // Check all months before inserting anything
        $check = $conn->prepare(
            "SELECT payment_month FROM rd_payments
             WHERE customer_id = ? AND payment_month = ?
             AND payment_year = ? LIMIT 1"
        );

        foreach ($months as $m) {
            $check->bind_param(
                "iii",
                $customerId,
                $m["month"],
                $m["year"]
            );
            $check->execute();

            if ($check->get_result()->num_rows > 0) {
                throw new Exception(
                    "Aa customer no " .
                    $m["month"] . "/" . $m["year"] .
                    " month pehla thi paid chhe. Advance payment cancel kari."
                );
            }
        }
        $check->close();

        $receipt = "ADV" . date("ymdHis") . random_int(100, 999);

        // Save the actual amount collected and discount
        $batch = $conn->prepare(
            "INSERT INTO advance_payments
             (customer_id, start_month, start_year, months_paid,
              monthly_amount, gross_amount, rebate_percent,
              rebate_amount, net_amount, payment_date, receipt_number)
             VALUES (?, ?, ?, 12, ?, ?, ?, ?, ?, ?, ?)"
        );

        

        $batch->bind_param(
            "iiidddddss",
            $customerId,
            $startMonth,
            $startYear,
            $monthlyAmount,
            $grossAmount,
            $rebatePercent,
            $rebateAmount,
            $netAmount,
            $paymentDate,
            $receipt
        );

        $batch->execute();
        $batch->close();

        // Mark all 12 instalments as paid.
        // amount stores the full RD instalment value, not the discounted cash.
        $insert = $conn->prepare(
            "INSERT INTO rd_payments
             (customer_id, payment_month, payment_year, amount,
              payment_date, receipt_number, payment_status, notes)
             VALUES (?, ?, ?, ?, ?, ?, 'Paid', ?)"
        );

        foreach ($months as $m) {
            $note = "12-month advance; batch " . $receipt .
                    "; ₹200 discount applied to total";

            $insert->bind_param(
                "iiidsss",
                $customerId,
                $m["month"],
                $m["year"],
                $monthlyAmount,
                $paymentDate,
                $receipt,
                $note
            );

            $insert->execute();
        }

        $insert->close();
        $conn->commit();

        $message = "Advance payment successfully saved.";
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

    } catch (Throwable $e) {
        if ($conn->connect_errno === 0) {
            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
            }
        }

        $error = $e instanceof mysqli_sql_exception &&
                 (int)$e->getCode() === 1062
            ? "Aa customer na koi month ni payment pehla thi chhe."
            : $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Advance RD Payment | Jalaram Investment</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f2f5fa;font-family:Arial,sans-serif;color:#243247}
.container{max-width:760px;margin:25px auto;padding:16px}
.card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 5px 22px #00000012}
h2{margin-top:0;color:#153e75}
label{display:block;font-weight:600;margin:15px 0 7px}
input,select{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:8px;font-size:16px}
button{width:100%;margin-top:20px;padding:13px;background:#1456a0;color:white;border:0;border-radius:8px;font-size:16px;font-weight:bold;cursor:pointer}
.summary{background:#f0f6ff;padding:16px;border-radius:10px;margin-top:18px;line-height:1.9}
.total{font-size:22px;color:#137443;font-weight:bold}
.msg{padding:12px;border-radius:8px;margin:12px 0}
.success{background:#e8f8ee;color:#17683b}
.fail{background:#ffeded;color:#a12222}
a{color:#1456a0}
small{color:#64748b}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h2>Jalaram Investment</h2>
<h3>12 Months Advance RD Payment</h3>

<?php if ($message): ?>
<div class="msg success">
<?= htmlspecialchars($message) ?><br>
Receipt Number: <strong><?= htmlspecialchars($receipt) ?></strong>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="msg fail"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
<input type="hidden" name="csrf_token"
       value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

<label>Search Customer</label>
<input type="text" id="search" placeholder="Name, mobile or customer code">
<label>Select Customer</label>
<select name="customer_id" id="customer_id" required>
<option value="">-- Select Customer --</option>
<?php foreach ($customers as $c): ?>
<option
 value="<?= (int)$c["id"] ?>"
 data-name="<?= htmlspecialchars($c["customer_name"], ENT_QUOTES) ?>"
 data-mobile="<?= htmlspecialchars($c["mobile"], ENT_QUOTES) ?>"
 data-code="<?= htmlspecialchars($c["customer_code"] ?? "", ENT_QUOTES) ?>"
 data-amount="<?= htmlspecialchars($c["monthly_amount"], ENT_QUOTES) ?>">
<?= htmlspecialchars($c["customer_name"]) ?> -
<?= htmlspecialchars($c["mobile"]) ?> -
<?= htmlspecialchars($c["customer_code"] ?? "") ?>
</option>
<?php endforeach; ?>
</select>

<label>First Month of Advance Payment</label>
<select name="start_month" required>
<?php for ($m=1;$m<=12;$m++): ?>
<option value="<?= $m ?>" <?= $m==(int)date("n") ? "selected" : "" ?>>
<?= date("F", mktime(0,0,0,$m,1)) ?>
</option>
<?php endfor; ?>
</select>

<label>Year</label>
<select name="start_year" required>
<?php for ($y=(int)date("Y")-1;$y<=(int)date("Y")+5;$y++): ?>
<option value="<?= $y ?>" <?= $y==(int)date("Y") ? "selected" : "" ?>>
<?= $y ?>
</option>
<?php endfor; ?>
</select>

<label>Payment Date</label>
<input type="date" name="payment_date"
       value="<?= date("Y-m-d") ?>" required>

<div class="summary">
<div>Monthly RD Amount: ₹<span id="monthly">0.00</span></div>
<div>12 Months Total: ₹<span id="gross">0.00</span></div>
<div>Rebate (3.3%): ₹<span id="rebate">0.00</span></div>
<div class="total">Customer Pays: ₹<span id="net">0.00</span></div>
<small>3.3% rebate is applied to the 12-month total.</small>
</div>

<button type="submit"
 onclick="return confirm('Please confirm 12-month advance payment.');">
Save Advance Payment
</button>
</form>

<p><a href="dashboard.php">← Back to Dashboard</a></p>
</div>
</div>

<script>
const search = document.getElementById('search');
const customerSelect = document.getElementById('customer_id');

search.addEventListener('input', function () {
  const term = this.value.toLowerCase();
  Array.from(customerSelect.options).forEach((option, index) => {
    if (index === 0) return;
    const text = (
      option.dataset.name + ' ' +
      option.dataset.mobile + ' ' +
      option.dataset.code
    ).toLowerCase();
    option.hidden = !text.includes(term);
  });
});

function updateAmounts() {
  const option = customerSelect.selectedOptions[0];
  const monthly = option ? Number(option.dataset.amount || 0) : 0;
  const gross = monthly * 12;
 const rebate = Math.round(gross * 3.3) / 100;
  const net = Math.max(0, gross - rebate);

  document.getElementById('monthly').textContent = monthly.toFixed(2);
  document.getElementById('gross').textContent = gross.toFixed(2);
  document.getElementById('rebate').textContent = rebate.toFixed(2);
  document.getElementById('net').textContent = net.toFixed(2);
}

customerSelect.addEventListener('change', updateAmounts);
updateAmounts();
</script>
</body>
</html>