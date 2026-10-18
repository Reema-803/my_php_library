<?php
session_start();
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

if (isGuest()) {
    $_SESSION['message'] = 'Guests cannot return books. Please login.';
    $_SESSION['message_type'] = 'error';
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';
$fine_amount = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_code = trim($_POST['book_code'] ?? '');
    
    if ($book_code) {
        $stmt = $conn->prepare("
            SELECT b.*, br.id as borrow_id, br.due_date 
            FROM borrowings br 
            JOIN books b ON br.book_id = b.id 
            WHERE b.book_code = ? AND br.user_id = ? AND br.status = 'Borrowed'
        ");
        $stmt->bind_param("si", $book_code, $user_id);
        $stmt->execute();
        $borrowing = $stmt->get_result()->fetch_assoc();
        
        if ($borrowing) {
            $return_date = date('Y-m-d');
            $due_date = $borrowing['due_date'];
            $days_late = (strtotime($return_date) - strtotime($due_date)) / (60 * 60 * 24);
            
            if ($days_late > 0) {
                $fine_amount = $days_late * 5;
            }
            
            $stmt = $conn->prepare("UPDATE borrowings SET status = 'Returned', return_date = ?, fine = ? WHERE id = ?");
            $stmt->bind_param("sdi", $return_date, $fine_amount, $borrowing['borrow_id']);
            $stmt->execute();
            
            $stmt = $conn->prepare("UPDATE books SET availability = 'Available' WHERE id = ?");
            $stmt->bind_param("i", $borrowing['id']);
            $stmt->execute();
            
            if ($fine_amount > 0) {
                $stmt = $conn->prepare("INSERT INTO payments (user_id, borrowing_id, amount, status) VALUES (?, ?, ?, 'Pending')");
                $stmt->bind_param("iid", $user_id, $borrowing['borrow_id'], $fine_amount);
                $stmt->execute();
                
                $message = "Book returned! Late return fine: ₹$fine_amount";
                $message_type = 'warning';
            } else {
                $message = 'Book returned successfully!';
                $message_type = 'success';
            }
        } else {
            $message = 'No active borrowing found for this book.';
            $message_type = 'error';
        }
    }
}

$stmt = $conn->prepare("
    SELECT b.*, br.id as borrow_id, br.due_date, br.borrow_date as borrowed_on
    FROM borrowings br 
    JOIN books b ON br.book_id = b.id 
    WHERE br.user_id = ? AND br.status = 'Borrowed'
    ORDER BY br.due_date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$borrowed_books = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Book - Library Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5em; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .return-box { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .icon { text-align: center; font-size: 4em; margin-bottom: 20px; }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
        .form-group input { width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn { width: 100%; padding: 15px; background: #667eea; color: white; border: none; border-radius: 10px; font-size: 18px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #5568d3; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-warning { background: #fff3cd; color: #856404; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .section { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section h3 { color: #333; margin-bottom: 20px; }
        .book-list { display: grid; gap: 15px; }
        .book-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f9f9f9; border-radius: 10px; }
        .book-info h4 { color: #333; margin-bottom: 5px; }
        .book-info p { color: #666; font-size: 14px; }
        .due-warning { background: #ffebee; border: 1px solid #ffcdd2; }
        .due-warning p { color: #c62828; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Return Book</h1>
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <div class="return-box">
            <div class="icon">↩️</div>
            <h2>Return a Book</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?>
                <?php if ($fine_amount > 0): ?>
                    <br><a href="payment.php">Pay Fine</a>
                <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Book Code / Barcode</label>
                    <input type="text" name="book_code" placeholder="Enter book code (e.g., BK001)" required>
                </div>
                <button type="submit" class="btn">Return Book</button>
            </form>
        </div>
        
        <div class="section">
            <h3>Books to Return</h3>
            <div class="book-list">
                <?php if ($borrowed_books->num_rows > 0): ?>
                    <?php while ($book = $borrowed_books->fetch_assoc()): ?>
                        <?php
                        $due_ts = strtotime($book['due_date']);
                        $today_ts = strtotime(date('Y-m-d'));
                        $is_overdue = $today_ts > $due_ts;
                        ?>
                        <div class="book-item <?= $is_overdue ? 'due-warning' : '' ?>">
                            <div class="book-info">
                                <h4><?= htmlspecialchars($book['title']) ?></h4>
                                <p>Code: <?= htmlspecialchars($book['book_code']) ?> | Borrowed: <?= htmlspecialchars($book['borrowed_on']) ?></p>
                                <p>Due: <?= htmlspecialchars($book['due_date']) ?> <?= $is_overdue ? '(OVERDUE)' : '' ?></p>
                            </div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="book_code" value="<?= htmlspecialchars($book['book_code']) ?>">
                                <button type="submit" class="btn" style="width: auto; padding: 10px 20px;">Return</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No books to return</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>