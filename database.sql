-- Library Kiosk Self-Service Management System
-- Database: smart_library

CREATE DATABASE IF NOT EXISTS smart_library;
USE smart_library;

-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20),
    card_id VARCHAR(50),
    name VARCHAR(100),
    email VARCHAR(100),
    is_guest TINYINT(1) DEFAULT 0,
    is_admin TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_phone (phone),
    UNIQUE KEY unique_card (card_id)
);

-- BOOKS TABLE
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_code VARCHAR(20) UNIQUE,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(100),
    category VARCHAR(50),
    availability ENUM('Available', 'Not Available') DEFAULT 'Available',
    location VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- BORROWINGS TABLE
CREATE TABLE IF NOT EXISTS borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    book_id INT,
    borrow_date DATE DEFAULT (CURRENT_DATE),
    due_date DATE,
    return_date DATE NULL,
    status ENUM('Borrowed', 'Returned') DEFAULT 'Borrowed',
    fine DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- PAYMENTS TABLE
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    borrowing_id INT,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Paid', 'Failed') DEFAULT 'Pending',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (borrowing_id) REFERENCES borrowings(id)
);

-- NOTIFICATIONS TABLE
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(200),
    message TEXT,
    type ENUM('reminder', 'fine', 'info') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- INSERT SAMPLE USERS
INSERT INTO users (phone, card_id, name, email, is_guest, is_admin) VALUES
('9876543210', 'CARD001', 'John Doe', 'john@example.com', 0, 0),
('9876543211', 'CARD002', 'Jane Smith', 'jane@example.com', 0, 0),
('9876543212', 'CARD003', 'Admin User', 'admin@library.com', 0, 1),
('9876543213', 'CARD004', 'Mike Johnson', 'mike@example.com', 0, 0);

-- INSERT SAMPLE BOOKS
INSERT INTO books (book_code, title, author, category, availability, location) VALUES
('BK001', 'The Great Gatsby', 'F. Scott Fitzgerald', 'Fiction', 'Available', 'A1-R1'),
('BK002', 'To Kill a Mockingbird', 'Harper Lee', 'Fiction', 'Available', 'A1-R2'),
('BK003', '1984', 'George Orwell', 'Science Fiction', 'Available', 'A2-R1'),
('BK004', 'Pride and Prejudice', 'Jane Austen', 'Romance', 'Available', 'A2-R2'),
('BK005', 'The Catcher in the Rye', 'J.D. Salinger', 'Fiction', 'Available', 'A1-R3'),
('BK006', 'Harry Potter and the Sorcerer\'s Stone', 'J.K. Rowling', 'Fantasy', 'Not Available', 'A3-R1'),
('BK007', 'The Hobbit', 'J.R.R. Tolkien', 'Fantasy', 'Available', 'A3-R2'),
('BK008', 'Brief History of Time', 'Stephen Hawking', 'Science', 'Available', 'A4-R1'),
('BK009', 'The Origin of Species', 'Charles Darwin', 'Science', 'Available', 'A4-R2'),
('BK010', 'Psychology of Money', 'Morgan Housel', 'Business', 'Available', 'A5-R1'),
('BK011', 'Atomic Habits', 'James Clear', 'Self-Help', 'Available', 'A5-R2'),
('BK012', 'Think and Grow Rich', 'Napoleon Hill', 'Business', 'Available', 'A5-R3'),
('BK013', 'The Alchemist', 'Paulo Coelho', 'Fiction', 'Available', 'A1-R4'),
('BK014', 'Sapiens', 'Yuval Noah Harari', 'History', 'Available', 'A6-R1'),
('BK015', 'Educated', 'Tara Westover', 'Biography', 'Available', 'A6-R2');

-- INSERT SAMPLE BORROWINGS
INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, return_date, status, fine) VALUES
(1, 6, '2026-04-01', '2026-04-08', NULL, 'Borrowed', 0),
(2, 1, '2026-04-05', '2026-04-12', '2026-04-12', 'Returned', 0),
(4, 3, '2026-03-20', '2026-03-27', '2026-04-10', 'Returned', 70);

-- INSERT SAMPLE PAYMENTS
INSERT INTO payments (user_id, borrowing_id, amount, status, payment_method, transaction_id) VALUES
(4, 3, 70, 'Paid', 'Razorpay', 'TXN003');

-- INSERT SAMPLE NOTIFICATIONS
INSERT INTO notifications (user_id, title, message, type) VALUES
(1, 'Due Reminder', 'Your book is due in 3 days', 'reminder'),
(4, 'Fine Applied', 'You have a fine of ₹70 for late return', 'fine');