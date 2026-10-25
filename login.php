<?php
session_start();
require_once __DIR__ . "/config.php";
if (!empty($_SESSION["admin_id"])) { header("Location: dashboard.php"); exit; }
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    if ($email === "" || $password === "") {
        $error = "Please enter email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($admin && password_verify($password, $admin["password_hash"])) {
            session_regenerate_id(true);
            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_username"] = $admin["username"];
            header("Location: dashboard.php"); exit;
        }
        $error = "Invalid email or password.";
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin Login</title>
<style>
body{font-family:Arial;background:#f1f5f9;margin:0;display:grid;min-height:100vh;place-items:center}.card{background:white;padding:28px;border-radius:14px;width:min(90%,380px);box-shadow:0 5px 20px #0001}input,button{width:100%;padding:12px;margin:8px 0;box-sizing:border-box}button{background:#123b70;color:white;border:0;border-radius:7px;cursor:pointer}.error{color:#b91c1c}
</style></head><body><form class="card" method="post" action="login.php"><h2>Jalaram Investment</h2><h3>Admin Login</h3>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<label>Email</label><input type="email" name="email" required>
<label>Password</label><input type="password" name="password" required>
<button type="submit">Login</button><p><a href="create_admin.php">Create / Reset Admin (local setup only)</a></p></form></body></html>