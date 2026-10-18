# Library Kiosk Self-Service Management System

## Setup Instructions

### 1. Database Setup
1. Open XAMPP Control Panel
2. Start Apache and MySQL
3. Open phpMyAdmin (http://localhost/phpmyadmin)
4. Click "Import" tab
5. Select the `database.sql` file and click "Go"

### 2. Project Files
1. Copy all files to `C:\xampp\htdocs\library-kiosk\`
2. Ensure the folder structure is:
   - /config/db.php
   - /index.php
   - /dashboard.php
   - /search.php
   - /map.php
   - /checkout.php
   - /return.php
   - /payment.php
   - /receipt.php
   - /recommend.php
   - /admin/admin.php
   - /admin/add_book.php
   - /admin/delete_book.php
   - /assets/style.css
   - /assets/script.js
   - /assets/map.png (optional - create your own library map)

### 3. Access the System
Open http://localhost/library-kiosk/index.php in your browser.

## Default Login Credentials

| Name | Phone | Card ID | Type |
|------|-------|---------|------|
| John Doe | 9876543210 | CARD001 | User |
| Jane Smith | 9876543211 | CARD002 | User |
| Admin User | 9876543212 | CARD003 | Admin |
| Mike Johnson | 9876543213 | CARD004 | User |

Use any phone number or card ID to register as a new user.

## Features

1. **Authentication** - Login via phone/card, auto-register, guest mode
2. **Book Management** - CRUD operations
3. **Search** - Search by title/category with voice search
4. **Indoor Navigation** - Interactive library map
5. **Checkout** - Borrow books with 7-day due date
6. **Return** - Return books with ₹5/day late fine
7. **Payment** - Razorpay simulation with receipts
8. **Notifications** - Due reminders and fine alerts
9. **AI Recommendations** - Personalized book suggestions
10. **Admin Panel** - Full management dashboard