<?php
session_start();
include '../includes/db.php';

$error = '';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // ఇక్కడ యూజర్ టేబుల్ నుండి ఈమెయిల్ మరియు రోల్ 'admin' ఉందో లేదో చెక్ చేస్తుంది
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // register.php లో వాడిన password_hash ని ఇక్కడ password_verify తో చెక్ చేస్తున్నాం
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['username'];
        
        // లాగిన్ సక్సెస్ అయ్యాక అడ్మిన్ డ్యాష్‌బోర్డ్ పేజీకి తీసుకువెళ్తుంది
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid admin credentials or you are not an admin.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            background-color: #f3f7f9;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
            width: 350px;
            border: 1px solid #e1e8ed;
        }
        .login-card h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #34495e;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .login-btn {
            background-color: #e74c3c; /* అడ్మిన్ కోసం రెడ్ కలర్ బటన్ */
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
        }
        .login-btn:hover {
            background-color: #c0392b;
        }
        .error-msg {
            color: #e74c3c;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Admin Login</h2>
    
    <form method="POST" action="login.php">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required placeholder="Enter admin email">
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required placeholder="Enter password">
        </div>
        <button type="submit" name="login" class="login-btn">Admin Login</button>
    </form>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?= $error; ?></div>
    <?php endif; ?>
</div>

</body>
</html>
