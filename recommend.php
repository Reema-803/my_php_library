<?php
session_start();
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

if (isGuest()) {
    $_SESSION['message'] = 'AI recommendations are for logged-in users only.';
    $_SESSION['message_type'] = 'error';
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT DISTINCT b.category 
    FROM borrowings br 
    JOIN books b ON br.book_id = b.id 
    WHERE br.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$categories_result = $stmt->get_result();

$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

if (count($categories) > 0) {
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $stmt = $conn->prepare("
        SELECT * FROM books 
        WHERE category IN ($placeholders) AND availability = 'Available'
        ORDER BY RAND() 
        LIMIT 10
    ");
    $stmt->bind_param(str_repeat('s', count($categories)), ...$categories);
    $stmt->execute();
    $recommendations = $stmt->get_result();
} else {
    $stmt = $conn->prepare("SELECT * FROM books WHERE availability = 'Available' ORDER BY RAND() LIMIT 10");
    $stmt->execute();
    $recommendations = $stmt->get_result();
}

$popular_stmt = $conn->prepare("
    SELECT b.*, COUNT(br.id) as borrow_count 
    FROM books b 
    LEFT JOIN borrowings br ON b.id = br.book_id 
    WHERE b.availability = 'Available'
    GROUP BY b.id 
    ORDER BY borrow_count DESC 
    LIMIT 5
");
$popular_stmt->execute();
$popular = $popular_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Recommendations - Library Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5em; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .ai-banner { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; }
        .ai-icon { font-size: 3em; }
        .ai-text h2 { margin-bottom: 10px; }
        .ai-text p { opacity: 0.9; }
        .section { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .section h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
        .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .book-card { background: #f9f9f9; padding: 20px; border-radius: 10px; transition: transform 0.2s; }
        .book-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .book-card h3 { color: #333; margin-bottom: 10px; font-size: 1.1em; }
        .book-card p { color: #666; font-size: 14px; margin-bottom: 5px; }
        .category-tag { display: inline-block; background: #e7f3ff; color: #004085; padding: 3px 12px; border-radius: 15px; font-size: 12px; margin-top: 10px; }
        .status { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-left: 10px; }
        .status-available { background: #d4edda; color: #155724; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; text-decoration: none; margin-top: 15px; }
        .btn:hover { background: #5568d3; }
        .no-books { text-align: center; color: #666; padding: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AI Recommendations</h1>
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <div class="ai-banner">
            <div class="ai-icon">🤖</div>
            <div class="ai-text">
                <h2>Smart Book Recommendations</h2>
                <p>Based on your borrowing history and preferences</p>
            </div>
        </div>
        
        <div class="section">
            <h2>Recommended For You</h2>
            <?php if ($recommendations && $recommendations->num_rows > 0): ?>
                <div class="book-grid">
                    <?php while ($book = $recommendations->fetch_assoc()): ?>
                        <div class="book-card">
                            <h3><?= htmlspecialchars($book['title']) ?></h3>
                            <p>Code: <?= htmlspecialchars($book['book_code']) ?></p>
                            <p>Author: <?= htmlspecialchars($book['author']) ?></p>
                            <p>Location: <?= htmlspecialchars($book['location']) ?></p>
                            <span class="category-tag"><?= htmlspecialchars($book['category']) ?></span>
                            <span class="status status-available">Available</span>
                            <a href="checkout.php?book_code=<?= urlencode($book['book_code']) ?>" class="btn">Checkout</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-books">
                    <p>No recommendations available. Try borrowing some books first!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Popular Books</h2>
            <?php if ($popular && $popular->num_rows > 0): ?>
                <div class="book-grid">
                    <?php while ($book = $popular->fetch_assoc()): ?>
                        <div class="book-card">
                            <h3><?= htmlspecialchars($book['title']) ?></h3>
                            <p>Code: <?= htmlspecialchars($book['book_code']) ?></p>
                            <p>Author: <?= htmlspecialchars($book['author']) ?></p>
                            <p>Borrowed: <?= $book['borrow_count'] ?> times</p>
                            <span class="category-tag"><?= htmlspecialchars($book['category']) ?></span>
                            <span class="status status-available">Available</span>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-books">No popular books yet.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>