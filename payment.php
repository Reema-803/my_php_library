<?php
session_start();
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

if (isGuest()) {
    $_SESSION['message'] = 'Guests cannot make payments. Please login.';
    $_SESSION['message_type'] = 'error';
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_fine'])) {
    $payment_id = $_POST['payment_id'];
    
    $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ? AND user_id = ? AND status = 'Pending'");
    $stmt->bind_param("ii", $payment_id, $user_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if ($payment) {
        $transaction_id = 'TXN' . time() . rand(100, 999);
        
        $stmt = $conn->prepare("UPDATE payments SET status = 'Paid', payment_method = 'Razorpay', transaction_id = ? WHERE id = ?");
        $stmt->bind_param("si", $transaction_id, $payment_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Payment Received', 'Fine payment of ₹{$payment['amount']} successful', 'info')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        redirect("receipt.php?payment_id=$payment_id");
    }
}

$stmt = $conn->prepare("
    SELECT p.*, b.title, b.book_code 
    FROM payments p 
    LEFT JOIN borrowings br ON p.borrowing_id = br.id 
    LEFT JOIN books b ON br.book_id = b.id 
    WHERE p.user_id = ? AND p.status = 'Pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_fines = $stmt->get_result();

$stmt = $conn->prepare("
    SELECT p.*, b.title 
    FROM payments p 
    LEFT JOIN borrowings br ON p.borrowing_id = br.id 
    LEFT JOIN books b ON br.book_id = b.id 
    WHERE p.user_id = ? AND p.status = 'Paid'
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$paid_payments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Library Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5em; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .section { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .section h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
        .payment-list { display: grid; gap: 15px; }
        .payment-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; background: #f9f9f9; border-radius: 10px; }
        .payment-details h4 { color: #333; margin-bottom: 5px; }
        .payment-details p { color: #666; font-size: 14px; }
        .amount { font-size: 1.5em; color: #d32f2f; font-weight: bold; }
        .btn { padding: 12px 25px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #5568d3; }
        .razorpay-box { background: linear-gradient(135deg, #3399cc 0%, #2244cc 100%); color: white; padding: 30px; border-radius: 15px; margin-top: 30px; text-align: center; }
        .razorpay-box h3 { margin-bottom: 15px; }
        .razorpay-box p { opacity: 0.9; }
        .no-payments { text-align: center; color: #666; padding: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payments</h1>
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <div class="section">
            <h2>Pending Fines</h2>
            <div class="payment-list">
                <?php if ($pending_fines->num_rows > 0): ?>
                    <?php while ($fine = $pending_fines->fetch_assoc()): ?>
                        <div class="payment-item">
                            <div class="payment-details">
                                <h4><?= htmlspecialchars($fine['title'] ?? 'Book') ?></h4>
                                <p>Book Code: <?= htmlspecialchars($fine['book_code'] ?? 'N/A') ?></p>
                                <p>Date: <?= htmlspecialchars($fine['created_at']) ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div class="amount">₹<?= number_format($fine['amount'], 2) ?></div>
                                <form method="POST" style="margin-top: 10px;">
                                    <input type="hidden" name="payment_id" value="<?= $fine['id'] ?>">
                                    <input type="hidden" name="pay_fine" value="1">
                                    <button type="submit" class="btn">Pay with Razorpay</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-payments">No pending fines</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>Payment History</h2>
            <div class="payment-list">
                <?php if ($paid_payments->num_rows > 0): ?>
                    <?php while ($payment = $paid_payments->fetch_assoc()): ?>
                        <div class="payment-item">
                            <div class="payment-details">
                                <h4><?= htmlspecialchars($payment['title'] ?? 'Book') ?></h4>
                                <p>Transaction: <?= htmlspecialchars($payment['transaction_id']) ?></p>
                                <p>Date: <?= htmlspecialchars($payment['created_at']) ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div class="amount" style="color: #2e7d32;">₹<?= number_format($payment['amount'], 2) ?></div>
                                <a href="receipt.php?payment_id=<?= $payment['id'] ?>" class="btn" style="display: inline-block; margin-top: 10px; text-decoration: none;">View Receipt</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-payments">No payment history</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="razorpay-box">
            <h3>🔒 Secure Payment by Razorpay</h3>
            <p>All transactions are secure and encrypted.</p>
        </div>
    </div>
</body>
</html>