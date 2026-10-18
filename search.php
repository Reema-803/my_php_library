<?php
session_start();
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$is_guest = isGuest();
$results = [];
$search_query = '';
$selected_category = $_GET['category'] ?? '';

$stmt = $conn->query("SELECT DISTINCT category FROM books WHERE category != '' ORDER BY category");
$categories = [];
while ($row = $stmt->fetch_assoc()) {
    $categories[] = $row['category'];
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $search_query = trim($_GET['q'] ?? '');
    $selected_category = $_GET['category'] ?? '';
    
    if ($search_query || $selected_category) {
        $conditions = [];
        $params = [];
        $types = '';
        
        if ($search_query) {
            $conditions[] = "(title LIKE ? OR category LIKE ?)";
            $params[] = "%$search_query%";
            $params[] = "%$search_query%";
            $types .= 'ss';
        }
        
        if ($selected_category) {
            $conditions[] = "category = ?";
            $params[] = $selected_category;
            $types .= 's';
        }
        
        if (count($conditions) > 0) {
            $sql = "SELECT * FROM books WHERE " . implode(' AND ', $conditions) . " ORDER BY title";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $results = $stmt->get_result();
        }
    } else {
        $results = $conn->query("SELECT * FROM books ORDER BY title LIMIT 50");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Books - Library Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5em; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .search-box { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .search-form { display: flex; gap: 15px; flex-wrap: wrap; }
        .search-form input { flex: 1; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; min-width: 200px; }
        .search-form input:focus { outline: none; border-color: #667eea; }
        .search-form button { padding: 15px 30px; background: #667eea; color: white; border: none; border-radius: 10px; font-size: 16px; cursor: pointer; }
        .search-form button:hover { background: #5568d3; }
        .voice-btn { padding: 15px 20px; background: #f44336; color: white; border: none; border-radius: 10px; cursor: pointer; font-size: 16px; }
        .voice-btn:hover { background: #da190b; }
        .voice-btn.listening { animation: pulse 1s infinite; background: #4caf50; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .section { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section h2 { color: #333; margin-bottom: 20px; }
        .results-grid { display: grid; gap: 15px; }
        .book-card { display: flex; justify-content: space-between; align-items: center; padding: 20px; background: #f9f9f9; border-radius: 10px; }
        .book-details h3 { color: #333; margin-bottom: 5px; }
        .book-details p { color: #666; font-size: 14px; }
        .book-details .category { display: inline-block; background: #e7f3ff; color: #004085; padding: 3px 10px; border-radius: 15px; font-size: 12px; margin-top: 5px; }
        .book-actions { display: flex; align-items: center; gap: 10px; }
        .status { padding: 8px 15px; border-radius: 20px; font-size: 14px; font-weight: 600; }
        .status-available { background: #d4edda; color: #155724; }
        .status-unavailable { background: #f8d7da; color: #721c24; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-disabled { background: #ccc; color: #666; cursor: not-allowed; }
        .no-results { text-align: center; padding: 40px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Search Books</h1>
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <div class="search-box">
            <form method="GET" action="" class="search-form">
                <input type="text" id="searchInput" name="q" placeholder="Search by title..." value="<?= htmlspecialchars($search_query) ?>">
                <select name="category" style="padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; min-width: 150px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $selected_category == $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Search</button>
                <button type="button" id="voiceBtn" class="voice-btn" title="Voice Search">🎤 Voice</button>
            </form>
            <p style="margin-top: 15px; color: #666; font-size: 14px;">
                Select a category or type a book title to search.</p>
            </p>
</div>
         
        <div class="section">
            <h2><?= ($search_query || $selected_category) ? ( $search_query ? 'Search Results for "' . htmlspecialchars($search_query) . '"' : 'Books in ' . htmlspecialchars($selected_category) ) : 'All Books' ?></h2>
            <div class="results-grid">
                <?php if ($results && $results->num_rows > 0): ?>
                    <?php while ($book = $results->fetch_assoc()): ?>
                        <div class="book-card">
                            <div class="book-details">
                                <h3><?= htmlspecialchars($book['title']) ?></h3>
                                <p>Code: <?= htmlspecialchars($book['book_code']) ?> | Author: <?= htmlspecialchars($book['author']) ?> | Location: <?= htmlspecialchars($book['location']) ?></p>
                                <span class="category"><?= htmlspecialchars($book['category']) ?></span>
                            </div>
                            <div class="book-actions">
                                <span class="status <?= $book['availability'] == 'Available' ? 'status-available' : 'status-unavailable' ?>">
                                    <?= htmlspecialchars($book['availability']) ?>
                                </span>
                                <?php if (!$is_guest && $book['availability'] == 'Available'): ?>
                                    <a href="checkout.php?book_code=<?= urlencode($book['book_code']) ?>" class="btn btn-primary">Checkout</a>
                                <?php elseif ($is_guest): ?>
                                    <span class="btn btn-disabled" title="Login to checkout">Checkout</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <p>No books found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        const voiceBtn = document.getElementById('voiceBtn');
        const searchInput = document.getElementById('searchInput');
        
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition || null;
        
        if (SpeechRecognition) {
            let recognition;
            
            voiceBtn.onclick = function() {
                try {
                    recognition = new SpeechRecognition();
                    recognition.continuous = false;
                    recognition.interimResults = false;
                    recognition.lang = 'en-US';
                    
                    voiceBtn.classList.add('listening');
                    voiceBtn.textContent = '🎤 Listening...';
                    
                    recognition.onresult = function(event) {
                        const transcript = event.results[0][0].transcript;
                        searchInput.value = transcript;
                        searchInput.form.submit();
                    };
                    
                    recognition.onerror = function(event) {
                        alert('Voice recognition error: ' + event.error);
                        voiceBtn.classList.remove('listening');
                        voiceBtn.textContent = '🎤 Voice';
                    };
                    
                    recognition.onend = function() {
                        voiceBtn.classList.remove('listening');
                        voiceBtn.textContent = '🎤 Voice';
                    };
                    
                    recognition.start();
                } catch (e) {
                    alert('Error starting voice recognition: ' + e.message);
                }
            };
        } else {
            voiceBtn.disabled = true;
            voiceBtn.title = 'Voice search not supported in this browser. Use Chrome for best experience.';
            voiceBtn.style.opacity = '0.5';
            voiceBtn.style.cursor = 'not-allowed';
        }
    </script>
</body>
</html>