<?php
session_start();

$email = $_SESSION['software_registration_email'] ?? '';

if ($email === '') {
    header('Location: software_register.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jalaram Investment - Payment</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:#f0f5fc;color:#19365f}
.header{background:linear-gradient(120deg,#102f62,#2164ae);color:white;padding:28px 18px;text-align:center}
.header h1{margin:0 0 8px;font-size:30px}
.header p{margin:7px 0;color:#dceaff}
.container{max-width:520px;margin:35px auto;padding:18px}
.card{background:white;border-radius:18px;overflow:hidden;box-shadow:0 8px 30px #183d651c}
.card-head{background:linear-gradient(120deg,#17427b,#286bc0);color:white;text-align:center;padding:25px 18px}
.card-head h2{margin:0 0 10px}
.card-head p{margin:0;line-height:1.5}
.content{padding:24px}
.price{background:#edf5ff;border-radius:14px;padding:22px;text-align:center;margin-bottom:24px}
.price p{margin:0 0 10px}
.amount{font-size:42px;font-weight:bold;color:#102f62}
.validity{margin-top:10px;color:#536b8b}
h3{font-size:17px;margin-bottom:14px}
.method{border:1px solid #c7d9f0;border-radius:12px;padding:16px;margin-bottom:20px;line-height:1.7}
.pay-btn{width:100%;border:0;border-radius:11px;padding:16px;background:#07866c;color:white;font-size:18px;font-weight:bold;cursor:pointer}
.pay-btn:hover{background:#066d58}
.pay-btn:disabled{opacity:.6;cursor:wait}
.note{margin-top:18px;background:#e7f7ef;color:#176348;padding:14px;border-radius:10px;font-size:13px;line-height:1.6}
#message{margin-top:14px;font-size:14px;line-height:1.5;overflow-wrap:anywhere}
.footer{text-align:center;padding:22px;color:#667b98;font-size:13px}
@media(max-width:560px){.header h1{font-size:25px}.container{margin:20px auto;padding:12px}.content{padding:19px}}
</style>
</head>
<body>

<header class="header">
  <h1>Jalaram Investment</h1>
  <p>Post Office RD Management System</p>
  <p>Your Trust • Our Responsibility</p>
</header>

<main class="container">
<section class="card">
  <div class="card-head">
    <h2>Software Login Renewal</h2>
    <p>Complete your annual payment to request account access.</p>
  </div>
  <div class="content">
    <div class="price">
      <p><strong>Annual Software Login Charge</strong></p>
      <div class="amount">₹500</div>
      <div class="validity">Validity: 12 Months</div>
    </div>

    <h3>Secure Payment Method</h3>
    <div class="method">
      <strong>Razorpay Checkout</strong><br>
      UPI • Debit/Credit Cards • Net Banking
    </div>

    <p><strong>Registered email:</strong><br>
      <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
    </p>

    <button class="pay-btn" id="payButton" type="button">
      🔒 Pay ₹500 Now
    </button>

    <div class="note">
      <strong>Secure Access</strong><br>
      Login access is granted only after the server verifies payment.
    </div>
    <div id="message" role="status" aria-live="polite"></div>
  </div>
</section>
</main>

<footer class="footer">
  © 2026 Jalaram Investment<br>
  Post Office RD Management System
</footer>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const payButton = document.getElementById('payButton');
const message = document.getElementById('message');

// Put your Razorpay TEST Key ID here.
// Never put your Key Secret in this file.
const razorpayKeyId = 'rzp_test_TqXDZD51ZdAFsT';

payButton.addEventListener('click', async function () {
  payButton.disabled = true;
  message.textContent = 'Creating secure payment order...';

  try {
    if (razorpayKeyId === 'YOUR_TEST_KEY_ID') {
      throw new Error('Add your Razorpay TEST Key ID in payment.php.');
    }

    // The server will identify the registered customer from the session.
    // Do not send customer identity from the browser.
    const orderResponse = await fetch('create_order.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({})
    });

    const order = await orderResponse.json();

    if (!orderResponse.ok || !order.id) {
      throw new Error(order.error || 'Payment order could not be created.');
    }

    const options = {
      key: rzp_test_TqXDZD51ZdAFsT,
      amount: order.amount,
      currency: order.currency,
      name: 'Jalaram Investment',
      description: 'Annual Software Login Charge',
      order_id: order.id,

      handler: async function (response) {
        message.textContent = 'Payment received. Verifying with server...';

        try {
          const verifyResponse = await fetch('verify_payment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(response)
          });

          const result = await verifyResponse.json();

          if (!verifyResponse.ok || !result.success) {
            throw new Error(
              result.message || result.error || 'Payment verification failed.'
            );
          }

          message.textContent = result.message ||
            'Payment verified. Please check your account status.';
        } catch (error) {
          message.textContent = error.message +
            ' Check the payment status before trying again.';
        } finally {
          payButton.disabled = false;
        }
      },

      modal: {
        ondismiss: function () {
          message.textContent =
            'Checkout closed. Check payment status before retrying.';
          payButton.disabled = false;
        }
      },

      theme: {color: '#17427b'}
    };

    const checkout = new Razorpay(options);

    checkout.on('payment.failed', function (response) {
      message.textContent =
        response.error?.description || 'Payment unsuccessful. Please try again.';
      payButton.disabled = false;
    });

    checkout.open();
  } catch (error) {
    message.textContent = error.message;
    payButton.disabled = false;
  }
});
</script>
</body>
</html>