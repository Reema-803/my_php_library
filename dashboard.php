<?php
session_start();
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$is_guest = isGuest();
$is_admin = isAdmin();

$message = '';
$message_type = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("
    SELECT b.*, br.due_date, br.status as borrow_status 
    FROM borrowings br 
    JOIN books b ON br.book_id = b.id 
    WHERE br.user_id = ? AND br.status = 'Borrowed'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$borrowed_books = $stmt->get_result();

$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

$total_borrowed = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM borrowings WHERE user_id = ? AND status = 'Borrowed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_borrowed = $stmt->get_result()->fetch_assoc()['cnt'];

$stmt = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE user_id = ? AND status = 'Pending' AND amount > 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_fines_result = $stmt->get_result()->fetch_assoc();
$total_fines = $total_fines_result['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Library Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { font-size: 1.5em; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-badge { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; }
        .logout-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .nav-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .nav-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: block; text-decoration: none; color: inherit; }
        .nav-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .nav-card h3 { color: #667eea; margin-bottom: 10px; font-size: 1.2em; }
        .nav-card p { color: #666; }
        .nav-card .icon { font-size: 2em; margin-bottom: 15px; }
        .section { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .section h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
        .book-list { display: grid; gap: 15px; }
        .book-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f9f9f9; border-radius: 10px; }
        .book-info h4 { color: #333; margin-bottom: 5px; }
        .book-info p { color: #666; font-size: 14px; }
        .status { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-available { background: #d4edda; color: #155724; }
        .status-unavailable { background: #f8d7da; color: #721c24; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-warning { background: #fff3cd; color: #856404; }
        .guest-notice { background: #e7f3ff; border: 1px solid #b3d7ff; padding: 15px; border-radius: 10px; margin-bottom: 20px; color: #004085; }
        .notification-item { padding: 12px; background: #f9f9f9; border-radius: 8px; margin-bottom: 10px; }
        .notification-item.unread { border-left: 4px solid #667eea; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Library Kiosk</h1>
        <div class="user-info">
            <span class="user-badge">Welcome, <?= htmlspecialchars($user['name']) ?></span>
            <?php if ($is_guest): ?>
                <span class="user-badge" style="background: #ffc107;">Guest User</span>
            <?php endif; ?>
            <?php if ($is_admin && !$is_guest): ?>
                <a href="admin/admin.php" class="logout-btn">Admin Panel</a>
            <?php endif; ?>
            <a href="index.php?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($is_guest): ?>
            <div class="guest-notice">
                <strong>Guest Mode:</strong> You can only search books. Login required to checkout books.
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="nav-cards">
            <a href="search.php" class="nav-card">
                <div class="icon">🔍</div>
                <h3>Search Books</h3>
                <p>Find books by title or category</p>
            </a>
            <a href="checkout.php" class="nav-card">
                <div class="icon">📚</div>
                <h3>Checkout Book</h3>
                <p>Borrow a book (<?= $total_borrowed ?> currently borrowed)</p>
            </a>
            <a href="return.php" class="nav-card">
                <div class="icon">↩️</div>
                <h3>Return Book</h3>
                <p>Return your borrowed books</p>
            </a>
            <a href="map.php" class="nav-card">
                <div class="icon">🗺️</div>
                <h3>Indoor Navigation</h3>
                <p>Find book locations</p>
            </a>
        </div>
        
        <div class="nav-cards">
            <?php if (!$is_guest): ?>
                <a href="recommend.php" class="nav-card">
                    <div class="icon">🤖</div>
                    <h3>AI Recommendations</h3>
                    <p>Personalized book suggestions</p>
                </a>
                <a href="payment.php" class="nav-card">
                    <div class="icon">💳</div>
                    <h3>Payments</h3>
                    <?php if ($total_fines > 0): ?>
                    <p style="color: #dc3545; font-weight: bold;">Fine: ₹<?= number_format($total_fines, 0) ?> Pending</p>
                    <?php else: ?>
                    <p>View payment history</p>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
            <?php if ($is_admin && !$is_guest): ?>
                <a href="admin/admin.php" class="nav-card">
                    <div class="icon">⚙️</div>
                    <h3>Admin Panel</h3>
                    <p>Manage library</p>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Currently Borrowed Books (<?= $total_borrowed ?>)</h2>
            <div class="book-list">
                <?php if ($borrowed_books->num_rows > 0): ?>
                    <?php while ($book = $borrowed_books->fetch_assoc()): ?>
                        <div class="book-item">
                            <div class="book-info">
                                <h4><?= htmlspecialchars($book['title']) ?></h4>
                                <p>Code: <?= htmlspecialchars($book['book_code']) ?> | Due: <?= htmlspecialchars($book['due_date']) ?></p>
                            </div>
                            <span class="status status-unavailable">Borrowed</span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #666; padding: 20px; text-align: center;">No books currently borrowed</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>Notifications</h2>
            <div class="book-list">
                <?php if ($notifications->num_rows > 0): ?>
                    <?php while ($notif = $notifications->fetch_assoc()): ?>
                        <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                            <strong><?= htmlspecialchars($notif['title']) ?></strong>
                            <p><?= htmlspecialchars($notif['message']) ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #666; padding: 20px; text-align: center;">No notifications</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>