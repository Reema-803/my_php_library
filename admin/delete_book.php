<?php
session_start();
require_once '../config/db.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$book_id = $_GET['id'] ?? 0;

if ($book_id) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    
    if ($book) {
        if ($book['availability'] == 'Available') {
            $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            
            $_SESSION['message'] = 'Book deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Cannot delete book that is currently borrowed.';
            $_SESSION['message_type'] = 'error';
        }
    }
}

redirect('admin.php');
?>