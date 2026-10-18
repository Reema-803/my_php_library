<?php
session_start();
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$payment_id = $_GET['payment_id'] ?? 0;

if (!$payment_id) {
    redirect('payment.php');
}

$stmt = $conn->prepare("SELECT p.*, b.title, b.book_code, u.name, u.email FROM payments p LEFT JOIN borrowings br ON p.borrowing_id = br.id LEFT JOIN books b ON br.book_id = b.id JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.user_id = ?");
$stmt->bind_param("ii", $payment_id, $user_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    redirect('payment.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Library Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .receipt { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        .receipt-header { text-align: center; border-bottom: 2px dashed #e0e0e0; padding-bottom: 20px; margin-bottom: 20px; }
        .receipt-header h1 { color: #667eea; font-size: 1.8em; margin-bottom: 5px; }
        .receipt-header p { color: #666; }
        .receipt-details { margin-bottom: 20px; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #666; }
        .detail-value { color: #333; font-weight: 600; }
        .total { font-size: 1.5em; color: #2e7d32; }
        .status { display: inline-block; background: #d4edda; color: #155724; padding: 5px 15px; border-radius: 20px; }
        .receipt-footer { text-align: center; padding-top: 20px; border-top: 2px dashed #e0e0e0; margin-top: 20px; }
        .receipt-footer p { color: #666; font-size: 14px; }
        .btn-print { background: #667eea; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; margin-top: 20px; }
        .btn-print:hover { background: #5568d3; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <h1>Library Kiosk</h1>
            <p>Payment Receipt</p>
        </div>
        
        <div class="receipt-details">
            <div class="detail-row">
                <span class="detail-label">Receipt No:</span>
                <span class="detail-value"><?= htmlspecialchars($payment['transaction_id']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">User Name:</span>
                <span class="detail-value"><?= htmlspecialchars($payment['name']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?= htmlspecialchars($payment['email']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Book:</span>
                <span class="detail-value"><?= htmlspecialchars($payment['title'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Book Code:</span>
                <span class="detail-value"><?= htmlspecialchars($payment['book_code'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($payment['created_at']))) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value"><?= htmlspecialchars($payment['payment_method'] ?? 'Razorpay') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="status">PAID</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount Paid:</span>
                <span class="detail-value total">₹<?= number_format($payment['amount'], 2) ?></span>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p>Thank you for your payment!</p>
            <p>Library Kiosk Self-Service System</p>
            <button class="btn-print" onclick="window.print()">Print Receipt</button>
            <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>