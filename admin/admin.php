<?php
session_start();
require_once '../config/db.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';
$message_type = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

$total_users = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$total_books = $conn->query("SELECT COUNT(*) as cnt FROM books")->fetch_assoc()['cnt'];
$total_borrowings = $conn->query("SELECT COUNT(*) as cnt FROM borrowings")->fetch_assoc()['cnt'];
$total_fines = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'Pending'")->fetch_assoc()['total'] ?? 0;

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$books = $conn->query("SELECT * FROM books ORDER BY created_at DESC LIMIT 10");
$borrowings = $conn->query("SELECT br.*, b.title, u.name FROM borrowings br JOIN books b ON br.book_id = b.id JOIN users u ON br.user_id = u.id ORDER BY br.borrow_date DESC LIMIT 10");
$payments = $conn->query("SELECT p.*, u.name FROM payments p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Library Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5em; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { color: #666; font-size: 14px; margin-bottom: 10px; text-transform: uppercase; }
        .stat-card .value { font-size: 2em; color: #667eea; font-weight: bold; }
        .section { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
        .section h2 { color: #333; }
        .btn { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; text-decoration: none; font-size: 14px; }
        .btn:hover { background: #5568d3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { color: #666; font-weight: 600; font-size: 14px; }
        td { color: #333; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin Panel</h1>
        <a href="../dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value"><?= $total_users ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Books</h3>
                <div class="value"><?= $total_books ?></div>
            </div>
            <div class="stat-card">
                <h3>Borrowings</h3>
                <div class="value"><?= $total_borrowings ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Fines</h3>
                <div class="value">₹<?= number_format($total_fines, 0) ?></div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>Users</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Card ID</th>
                        <th>Type</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($user['card_id'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($user['is_admin']): ?>Admin
                                <?php elseif ($user['is_guest']): ?>Guest
                                <?php else: ?>Member<?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>Books</h2>
                <a href="add_book.php" class="btn">+ Add Book</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Availability</th>
                        <th>Location</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($book = $books->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($book['book_code']) ?></td>
                            <td><?= htmlspecialchars($book['title']) ?></td>
                            <td><?= htmlspecialchars($book['author']) ?></td>
                            <td><?= htmlspecialchars($book['category']) ?></td>
                            <td><?= htmlspecialchars($book['availability']) ?></td>
                            <td><?= htmlspecialchars($book['location']) ?></td>
                            <td>
                                <a href="add_book.php?edit=<?= $book['id'] ?>" class="btn" style="background:#28a745; margin-right:5px;">Edit</a>
                                <a href="delete_book.php?id=<?= $book['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this book?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>Recent Transactions</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Book</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Fine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($br = $borrowings->fetch_assoc()): ?>
                        <tr>
                            <td><?= $br['id'] ?></td>
                            <td><?= htmlspecialchars($br['name']) ?></td>
                            <td><?= htmlspecialchars($br['title']) ?></td>
                            <td><?= htmlspecialchars($br['borrow_date']) ?></td>
                            <td><?= htmlspecialchars($br['due_date']) ?></td>
                            <td><?= htmlspecialchars($br['status']) ?></td>
                            <td>₹<?= number_format($br['fine'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>Payments</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Transaction ID</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pay = $payments->fetch_assoc()): ?>
                        <tr>
                            <td><?= $pay['id'] ?></td>
                            <td><?= htmlspecialchars($pay['name']) ?></td>
                            <td>₹<?= number_format($pay['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($pay['status']) ?></td>
                            <td><?= htmlspecialchars($pay['transaction_id'] ?? 'N/A') ?></td>
                            <td><?= date('d M Y', strtotime($pay['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>