<?php
session_start();
require_once __DIR__ . "/config.php";
require_admin();

$message = "";
$error = "";

$customers = $conn->query(
    "SELECT id, customer_code, customer_name, mobile, monthly_amount
     FROM customers
     ORDER BY customer_name"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $customerId = (int)($_POST["customer_id"] ?? 0);
    $month = (int)($_POST["payment_month"] ?? 0);
    $year = (int)($_POST["payment_year"] ?? 0);
    $payDate = $_POST["payment_date"] ?? "";

    if (
        $customerId <= 0 ||
        $month < 1 || $month > 12 ||
        $year < 2000 || $year > 2100 ||
        $payDate === ""
    ) {
        $error = "Please fill all required fields correctly.";
    } else {
        try {
            $stmt = $conn->prepare(
                "SELECT monthly_amount FROM customers WHERE id = ?"
            );
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $customer = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$customer) {
                throw new Exception("Customer not found.");
            }

            $amount = (float)$customer["monthly_amount"];

            if ($amount <= 0) {
                throw new Exception("Customer RD amount is invalid.");
            }

            $receipt = "JRD" . date("ymdHis") . random_int(100, 999);
            $status = "Paid";
            $notes = "";

            $stmt = $conn->prepare(
                "INSERT INTO rd_payments
                (customer_id, payment_month, payment_year, amount,
                 payment_date, receipt_number, payment_status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iiidssss",
                $customerId, $month, $year, $amount,
                $payDate, $receipt, $status, $notes
            );

            $stmt->execute();
            $stmt->close();

            $message = "Payment saved successfully! Receipt No: "
                . $receipt . " | Amount: ₹"
                . number_format($amount, 2);

        } catch (Throwable $e) {
            if (
                $e instanceof mysqli_sql_exception &&
                ($e->getCode() == 1062 || $e->getCode() == 1022)
            ) {
                $error = "Payment for this customer and month may already exist.";
            } else {
                $error = "Payment could not be saved: " . $e->getMessage();
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Monthly RD Payment</title>

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
    box-shadow: 0 4px 12px #0001;
}
input, select, button {
    width: 100%;
    padding: 11px;
    margin: 7px 0 14px;
    box-sizing: border-box;
}
button {
    background: #123b70;
    color: white;
    border: 0;
    border-radius: 7px;
    cursor: pointer;
}
.ok { color: green; overflow-wrap: anywhere; }
.err { color: #b91c1c; overflow-wrap: anywhere; }
.amount {
    background: #e8f5e9;
    padding: 12px;
    border-radius: 7px;
    font-weight: bold;
}
.customer-results {
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    margin-top: -8px;
    margin-bottom: 14px;
    max-height: 220px;
    overflow-y: auto;
    display: none;
}
.customer-option {
    padding: 12px;
    cursor: pointer;
    border-bottom: 1px solid #e2e8f0;
}
.customer-option:last-child { border-bottom: none; }
.customer-option:hover { background: #eff6ff; }
.customer-option.selected { background: #dbeafe; }
.small {
    display: block;
    color: #64748b;
    font-size: 13px;
    margin-top: 4px;
}
</style>
</head>

<body>
<div class="card">

<h2>Monthly RD Payment</h2>

<?php if ($message !== ""): ?>
<p class="ok"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<?php if ($error !== ""): ?>
<p class="err"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post" id="paymentForm">

<label>Search Customer *</label>
<input
    type="text"
    id="customerSearch"
    placeholder="Type name, mobile or Customer ID..."
    autocomplete="off"
    required
>

<div id="customerResults" class="customer-results"></div>

<input type="hidden" name="customer_id" id="customerId" required>

<div class="amount">
    Selected Customer: <span id="selectedCustomer">None</span><br><br>
    Fixed Monthly RD Amount: ₹<span id="amount">0.00</span>
</div>

<label>Payment Month *</label>
<select name="payment_month" required>
<option value="">Select month</option>
<?php
$months = [
    1 => "January", 2 => "February", 3 => "March",
    4 => "April", 5 => "May", 6 => "June",
    7 => "July", 8 => "August", 9 => "September",
    10 => "October", 11 => "November", 12 => "December"
];
foreach ($months as $num => $monthName):
?>
<option value="<?= $num ?>" <?= (date("n") == $num ? "selected" : "") ?>>
<?= $monthName ?>
</option>
<?php endforeach; ?>
</select>

<label>Payment Year *</label>
<select name="payment_year" required>
<?php for ($y = (int)date("Y") - 1; $y <= (int)date("Y") + 1; $y++): ?>
<option value="<?= $y ?>" <?= ($y == date("Y") ? "selected" : "") ?>>
<?= $y ?>
</option>
<?php endfor; ?>
</select>

<label>Payment Date *</label>
<input type="date" name="payment_date"
       value="<?= date("Y-m-d") ?>" required>

<button type="submit">Save RD Payment</button>
</form>

<p><a href="dashboard.php">← Dashboard</a></p>
</div>

<script>
const customers = [
<?php
$customers->data_seek(0);
$items = [];

while ($c = $customers->fetch_assoc()) {
    $items[] = [
        "id" => (int)$c["id"],
        "code" => $c["customer_code"],
        "name" => $c["customer_name"],
        "mobile" => $c["mobile"],
        "amount" => (float)$c["monthly_amount"]
    ];
}

foreach ($items as $i => $c):
?>
{
    id: <?= $c["id"] ?>,
    code: <?= json_encode($c["code"]) ?>,
    name: <?= json_encode($c["name"]) ?>,
    mobile: <?= json_encode($c["mobile"]) ?>,
    amount: <?= json_encode($c["amount"]) ?>
}<?= $i < count($items) - 1 ? "," : "" ?>
<?php endforeach; ?>
];

const search = document.getElementById("customerSearch");
const results = document.getElementById("customerResults");
const customerId = document.getElementById("customerId");
const amountDisplay = document.getElementById("amount");
const selectedCustomer = document.getElementById("selectedCustomer");

function renderCustomers() {
    const q = search.value.trim().toLowerCase();
    results.innerHTML = "";

    if (!q) {
        results.style.display = "none";
        return;
    }

    const matches = customers.filter(c =>
        String(c.name).toLowerCase().includes(q) ||
        String(c.mobile).toLowerCase().includes(q) ||
        String(c.code).toLowerCase().includes(q)
    ).slice(0, 50);

    if (matches.length === 0) {
        results.innerHTML =
            '<div class="customer-option">No customer found</div>';
    } else {
        matches.forEach(c => {
            const item = document.createElement("div");
            item.className = "customer-option";

            const name = document.createElement("strong");
            name.textContent = c.name;

            const detail = document.createElement("span");
            detail.className = "small";
            detail.textContent =
                c.code + " | " + c.mobile +
                " | ₹" + Number(c.amount).toFixed(2);

            item.appendChild(name);
            item.appendChild(detail);

            item.addEventListener("click", () => {
                customerId.value = c.id;
                search.value = c.name + " - " + c.mobile;
                selectedCustomer.textContent = c.name;
                amountDisplay.textContent = Number(c.amount).toFixed(2);
                results.style.display = "none";
            });

            results.appendChild(item);
        });
    }

    results.style.display = "block";
}

search.addEventListener("input", () => {
    customerId.value = "";
    selectedCustomer.textContent = "None";
    amountDisplay.textContent = "0.00";
    renderCustomers();
});

document.addEventListener("click", e => {
    if (!results.contains(e.target) && e.target !== search) {
        results.style.display = "none";
    }
});

document.getElementById("paymentForm").addEventListener("submit", e => {
    if (!customerId.value) {
        e.preventDefault();
        alert("Please search and select a customer first.");
        search.focus();
    }
});
</script>

</body>
</html>