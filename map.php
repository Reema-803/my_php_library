<?php
session_start();
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$book_location = '';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['location'])) {
    $book_location = trim($_GET['location']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indoor Navigation - Library Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5em; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .search-box { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .search-form { display: flex; gap: 15px; }
        .search-form input { flex: 1; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; }
        .search-form input:focus { outline: none; border-color: #667eea; }
        .search-form button { padding: 15px 30px; background: #667eea; color: white; border: none; border-radius: 10px; font-size: 16px; cursor: pointer; }
        .map-section { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .map-section h2 { color: #333; margin-bottom: 20px; }
        .map-container { position: relative; background: #f0f0f0; border-radius: 10px; padding: 20px; min-height: 400px; }
        .map-image { width: 100%; height: auto; border-radius: 10px; }
        .aisle { display: flex; gap: 20px; margin-bottom: 20px; }
        .aisle-column { flex: 1; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .aisle-column h3 { color: #667eea; margin-bottom: 15px; }
        .rack { background: #f9f9f9; padding: 10px; margin-bottom: 10px; border-radius: 5px; text-align: center; cursor: pointer; transition: background 0.2s; }
        .rack:hover { background: #667eea; color: white; }
        .location-marker { position: absolute; background: #ff5722; color: white; padding: 10px 15px; border-radius: 50%; font-weight: bold; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
        .location-info { background: #e8f5e9; border: 2px solid #4caf50; padding: 20px; border-radius: 10px; margin-top: 20px; text-align: center; }
        .location-info h3 { color: #2e7d32; margin-bottom: 10px; }
        .location-info p { color: #666; }
        .legend { display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap; }
        .legend-item { display: flex; align-items: center; gap: 10px; }
        .legend-box { width: 30px; height: 30px; border-radius: 5px; }
        .legend-fiction { background: #e3f2fd; border: 2px solid #2196f3; }
        .legend-science { background: #e8f5e9; border: 2px solid #4caf50; }
        .legend-business { background: #fff3e0; border: 2px solid #ff9800; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Indoor Navigation</h1>
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <div class="search-box">
            <form method="GET" action="" class="search-form">
                <input type="text" name="location" placeholder="Enter book code (e.g., BK001) or location (e.g., A1-R1)" value="<?= htmlspecialchars($book_location) ?>">
                <button type="submit">Find Location</button>
            </form>
        </div>
        
        <?php if ($book_location): ?>
            <?php
            $stmt = $conn->prepare("SELECT * FROM books WHERE book_code = ? OR location = ?");
            $stmt->bind_param("ss", $book_location, $book_location);
            $stmt->execute();
            $book = $stmt->get_result()->fetch_assoc();
            ?>
            <?php if ($book): ?>
                <div class="location-info">
                    <h3>📍 Book Found</h3>
                    <p><strong><?= htmlspecialchars($book['title']) ?></strong></p>
                    <p>Location: <strong><?= htmlspecialchars($book['location']) ?></strong></p>
                    <p>Aisle: <?= strtok($book['location'], '-') ?> | Rack: <?= strtok('-') ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="map-section">
            <h2>Library Floor Plan</h2>
            <p style="margin-bottom: 20px; color: #666;">Navigate to find books. Click on a rack to see available books.</p>
            
            <div class="map-container">
                <div class="aisle">
                    <div class="aisle-column">
                        <h3>Aisle A1 - Fiction</h3>
                        <div class="rack" onclick="showLocation('A1-R1')">Rack R1</div>
                        <div class="rack" onclick="showLocation('A1-R2')">Rack R2</div>
                        <div class="rack" onclick="showLocation('A1-R3')">Rack R3</div>
                        <div class="rack" onclick="showLocation('A1-R4')">Rack R4</div>
                    </div>
                    <div class="aisle-column">
                        <h3>Aisle A2 - Fiction</h3>
                        <div class="rack" onclick="showLocation('A2-R1')">Rack R1</div>
                        <div class="rack" onclick="showLocation('A2-R2')">Rack R2</div>
                    </div>
                    <div class="aisle-column">
                        <h3>Aisle A3 - Fantasy</h3>
                        <div class="rack" onclick="showLocation('A3-R1')">Rack R1</div>
                        <div class="rack" onclick="showLocation('A3-R2')">Rack R2</div>
                    </div>
                </div>
                <div class="aisle">
                    <div class="aisle-column">
                        <h3>Aisle A4 - Science</h3>
                        <div class="rack" onclick="showLocation('A4-R1')">Rack R1</div>
                        <div class="rack" onclick="showLocation('A4-R2')">Rack R2</div>
                    </div>
                    <div class="aisle-column">
                        <h3>Aisle A5 - Business/Self-Help</h3>
                        <div class="rack" onclick="showLocation('A5-R1')">Rack R1</div>
                        <div class="rack" onclick="showLocation('A5-R2')">Rack R2</div>
                        <div class="rack" onclick="showLocation('A5-R3')">Rack R3</div>
                    </div>
                    <div class="aisle-column">
                        <h3>Aisle A6 - History/Biography</h3>
                        <div class="rack" onclick="showLocation('A6-R1')">Rack R1</div>
                        <div class="rack" onclick="showLocation('A6-R2')">Rack R2</div>
                    </div>
                </div>
            </div>
            
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-box legend-fiction"></div>
                    <span>Fiction</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-science"></div>
                    <span>Science</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-business"></div>
                    <span>Business</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showLocation(location) {
            window.location.href = 'map.php?location=' + encodeURIComponent(location);
        }
    </script>
</body>
</html>