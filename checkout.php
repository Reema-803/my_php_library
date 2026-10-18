<?php
session_start();
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

if (isGuest()) {
    $_SESSION['message'] = 'Guests cannot checkout books. Please login.';
    $_SESSION['message_type'] = 'error';
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_code = trim($_POST['book_code'] ?? '');
    
    if ($book_code) {
        $stmt = $conn->prepare("SELECT * FROM books WHERE book_code = ?");
        $stmt->bind_param("s", $book_code);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
        
        if ($book) {
            if ($book['availability'] == 'Not Available') {
                $message = 'Book is not available for checkout.';
                $message_type = 'error';
            } else {
                $borrow_date = date('Y-m-d');
                $due_date = date('Y-m-d', strtotime('+7 days'));
                
                $stmt = $conn->prepare("INSERT INTO borrowings (user_id, book_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $user_id, $book['id'], $borrow_date, $due_date);
                
                if ($stmt->execute()) {
                    $stmt = $conn->prepare("UPDATE books SET availability = 'Not Available' WHERE id = ?");
                    $stmt->bind_param("i", $book['id']);
                    $stmt->execute();
                    
                    $message = 'Book checkout successful! Due date: ' . $due_date;
                    $message_type = 'success';
                }
            }
        } else {
            $message = 'Book not found.';
            $message_type = 'error';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['book_code'])) {
    $book_code = trim($_GET['book_code']);
    
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_code = ?");
    $stmt->bind_param("s", $book_code);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    
    if ($book && $book['availability'] == 'Available') {
        $borrow_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+7 days'));
        
        $stmt = $conn->prepare("INSERT INTO borrowings (user_id, book_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $book['id'], $borrow_date, $due_date);
        
        if ($stmt->execute()) {
            $stmt = $conn->prepare("UPDATE books SET availability = 'Not Available' WHERE id = ?");
            $stmt->bind_param("i", $book['id']);
            $stmt->execute();
            
            $message = 'Book checkout successful! Due date: ' . $due_date;
            $message_type = 'success';
        }
    } else {
        $message = 'Book not available or not found.';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Book - Library Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5em; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .container { max-width: 600px; margin: 30px auto; padding: 0 20px; }
        .checkout-box { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .icon { text-align: center; font-size: 4em; margin-bottom: 20px; }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
        .form-group input { width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn { width: 100%; padding: 15px; background: #667eea; color: white; border: none; border-radius: 10px; font-size: 18px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #5568d3; }
        .info-box { background: #e8f5e9; border: 2px solid #4caf50; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .info-box p { color: #2e7d32; margin-bottom: 10px; }
        .info-box strong { display: block; font-size: 1.2em; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Checkout Book</h1>
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <div class="checkout-box">
            <div class="icon">📚</div>
            <h2>Borrow a Book</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Book Code / Barcode</label>
                    <input type="text" name="book_code" placeholder="Enter book code (e.g., BK001)" required>
                </div>
                <button type="submit" class="btn">Checkout Book</button>
            </form>
            
            <div class="info-box">
                <p><strong>📋 Checkout Details</strong></p>
                <p>• Return due in 7 days</p>
                <p>• Late fine: ₹5 per day</p>
                <p>• Maximum 3 books at a time</p>
            </div>
        </div>
    </div>
</body>
</html>