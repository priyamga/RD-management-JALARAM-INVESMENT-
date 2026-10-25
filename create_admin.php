<?php
require_once __DIR__ . "/config.php";
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    if (!filter_var($username, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        $message = "Enter a valid email and password with at least 8 characters.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admins (username,password_hash) VALUES (?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)");
        $stmt->bind_param("ss", $username, $hash);
        $stmt->execute(); $stmt->close();
        $message = "Admin saved. Delete create_admin.php now, then login.";
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create Admin</title>
<style>body{font-family:Arial;background:#f1f5f9;padding:25px}.card{background:white;padding:25px;max-width:420px;margin:auto;border-radius:12px}input,button{width:100%;padding:12px;margin:8px 0;box-sizing:border-box}button{background:#123b70;color:white;border:0}</style></head><body><form class="card" method="post"><h2>Create / Reset Admin</h2><p><?= htmlspecialchars($message) ?></p><label>Admin email</label><input type="email" name="username" required><label>New password (8+ characters)</label><input type="password" name="password" minlength="8" required><button>Save Admin</button><p><a href="login.php">Back to login</a></p></form></body></html>