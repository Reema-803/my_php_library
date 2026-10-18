<?php
session_start();
require_once 'config/db.php';

$message = '';
$message_type = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $card_id = trim($_POST['card_id'] ?? '');
    $guest_mode = isset($_POST['guest_mode']);

    $guest_name = trim($_POST['guest_name'] ?? '');
    
    if ($guest_mode) {
        $username = $guest_name ?: 'Guest_' . rand(1000, 9999);
        
        if ($guest_name) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE name = ? AND is_guest = 1 LIMIT 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['is_guest'] = 1;
                $_SESSION['is_admin'] = 0;
                redirect('dashboard.php');
            } else {
                $guest_phone = 'GUEST' . preg_replace('/[^a-zA-Z0-9]/', '', $guest_name);
                $stmt = $conn->prepare("INSERT INTO users (name, phone, is_guest) VALUES (?, ?, 1)");
                $stmt->bind_param("ss", $username, $guest_phone);
                
                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['name'] = $username;
                    $_SESSION['is_guest'] = 1;
                    redirect('dashboard.php');
                }
            }
        } else {
            $guest_phone = 'GUEST' . rand(100000, 999999);
            $stmt = $conn->prepare("INSERT INTO users (name, phone, is_guest) VALUES (?, ?, 1)");
            $stmt->bind_param("ss", $username, $guest_phone);
            
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['name'] = $username;
                $_SESSION['is_guest'] = 1;
                redirect('dashboard.php');
            }
        }
    } elseif ($phone || $card_id || $name) {
        if ($phone) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['is_guest'] = $user['is_guest'];
                $_SESSION['is_admin'] = $user['is_admin'];
                redirect('dashboard.php');
            }
        }
        
        if ($card_id) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE card_id = ?");
            $stmt->bind_param("s", $card_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['is_guest'] = $user['is_guest'];
                $_SESSION['is_admin'] = $user['is_admin'];
                redirect('dashboard.php');
            }
        }
        
        if ($name) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE name = ? AND is_guest = 0 LIMIT 1");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['is_guest'] = $user['is_guest'];
                $_SESSION['is_admin'] = $user['is_admin'];
                redirect('dashboard.php');
            }
        }
        
        $user_name = $name ?: 'User_' . rand(1000, 9999);
        $phone_val = $phone ?: '';
        $card_id_val = $card_id ?: '';
        $stmt = $conn->prepare("INSERT INTO users (name, phone, card_id, is_guest) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $user_name, $phone_val, $card_id_val);
        
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['name'] = $user_name;
            $_SESSION['is_guest'] = 0;
            redirect('dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Kiosk - Login</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .login-container { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 50px; width: 100%; max-width: 450px; }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #667eea; font-size: 2em; margin-bottom: 10px; }
        .logo p { color: #666; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
        .form-group input { width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; transition: border-color 0.3s; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-size: 18px; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #f0f0f0; color: #333; margin-top: 15px; }
        .btn-secondary:hover { background: #e0e0e0; }
        .divider { text-align: center; margin: 25px 0; color: #999; position: relative; }
        .divider::before { content: ''; position: absolute; left: 0; top: 50%; width: 100%; height: 1px; background: #e0e0e0; }
        .divider span { background: white; padding: 0 15px; position: relative; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Library Kiosk</h1>
            <p>Self-Service Management System</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" placeholder="Enter your name (optional)">
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="Enter phone number">
            </div>
            <div class="form-group">
                <label>Card ID</label>
                <input type="text" name="card_id" placeholder="Enter card ID">
            </div>
            <button type="submit" class="btn">Login / Register</button>
        </form>
        
        <div class="divider"><span>OR</span></div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Guest Name (Optional)</label>
                <input type="text" name="guest_name" placeholder="Enter your name">
            </div>
            <input type="hidden" name="guest_mode" value="1">
            <input type="hidden" name="phone" value="">
            <button type="submit" class="btn btn-secondary">Continue as Guest (Search Only)</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
            Guests can only search books. Login required for checkout.
        </p>
    </div>
</body>
</html>