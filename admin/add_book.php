<?php
session_start();
require_once '../config/db.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';
$message_type = '';
$edit_mode = false;
$book_id = $_GET['edit'] ?? 0;
$book_data = null;

if ($book_id) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $book_data = $result->fetch_assoc();
        $edit_mode = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_code = trim($_POST['book_code']);
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $category = trim($_POST['category']);
    $location = trim($_POST['location']);
    $availability = $_POST['availability'] ?? 'Available';
    $update_id = $_POST['book_id'] ?? 0;
    
    if ($book_code && $title) {
        $author = $author ?: '';
        $category = $category ?: '';
        
        if ($update_id) {
            $stmt = $conn->prepare("UPDATE books SET book_code = ?, title = ?, author = ?, category = ?, availability = ?, location = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $book_code, $title, $author, $category, $availability, $location, $update_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Book updated successfully!';
                $_SESSION['message_type'] = 'success';
                redirect('admin.php');
            } else {
                $message = 'Error updating book.';
                $message_type = 'error';
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO books (book_code, title, author, category, availability, location) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $book_code, $title, $author, $category, $availability, $location);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Book added successfully!';
                $_SESSION['message_type'] = 'success';
                redirect('admin.php');
            } else {
                $message = 'Error adding book. Code may already exist.';
                $message_type = 'error';
            }
        }
    } else {
        $message = 'Book code and title are required.';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_mode ? 'Edit' : 'Add' ?> Book - Library Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5em; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .container { max-width: 600px; margin: 30px auto; padding: 0 20px; }
        .form-box { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn { width: 100%; padding: 15px; background: #667eea; color: white; border: none; border-radius: 10px; font-size: 18px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #5568d3; }
        .btn-secondary { background: #6c757d; margin-top: 10px; }
        .btn-secondary:hover { background: #5a6268; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= $edit_mode ? 'Edit Book' : 'Add Book' ?></h1>
        <a href="admin.php" class="back-btn">← Back to Admin</a>
    </div>
    
    <div class="container">
        <div class="form-box">
            <h2><?= $edit_mode ? 'Edit Book' : 'Add New Book' ?></h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php if ($edit_mode): ?>
                <input type="hidden" name="book_id" value="<?= $book_id ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Book Code *</label>
                    <input type="text" name="book_code" placeholder="e.g., BK016" value="<?= $book_data ? htmlspecialchars($book_data['book_code']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="Book title" value="<?= $book_data ? htmlspecialchars($book_data['title']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" placeholder="Author name" value="<?= $book_data ? htmlspecialchars($book_data['author']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="">Select category</option>
                        <option value="Fiction" <?= $book_data && $book_data['category'] == 'Fiction' ? 'selected' : '' ?>>Fiction</option>
                        <option value="Science Fiction" <?= $book_data && $book_data['category'] == 'Science Fiction' ? 'selected' : '' ?>>Science Fiction</option>
                        <option value="Fantasy" <?= $book_data && $book_data['category'] == 'Fantasy' ? 'selected' : '' ?>>Fantasy</option>
                        <option value="Science" <?= $book_data && $book_data['category'] == 'Science' ? 'selected' : '' ?>>Science</option>
                        <option value="Business" <?= $book_data && $book_data['category'] == 'Business' ? 'selected' : '' ?>>Business</option>
                        <option value="Self-Help" <?= $book_data && $book_data['category'] == 'Self-Help' ? 'selected' : '' ?>>Self-Help</option>
                        <option value="History" <?= $book_data && $book_data['category'] == 'History' ? 'selected' : '' ?>>History</option>
                        <option value="Biography" <?= $book_data && $book_data['category'] == 'Biography' ? 'selected' : '' ?>>Biography</option>
                        <option value="Romance" <?= $book_data && $book_data['category'] == 'Romance' ? 'selected' : '' ?>>Romance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" placeholder="e.g., A1-R1" value="<?= $book_data ? htmlspecialchars($book_data['location']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Availability</label>
                    <select name="availability">
                        <option value="Available" <?= $book_data && $book_data['availability'] == 'Available' ? 'selected' : '' ?>>Available</option>
                        <option value="Not Available" <?= $book_data && $book_data['availability'] == 'Not Available' ? 'selected' : '' ?>>Not Available</option>
                    </select>
                </div>
                <button type="submit" class="btn"><?= $edit_mode ? 'Update Book' : 'Add Book' ?></button>
            </form>
        </div>
    </div>
</body>
</html>