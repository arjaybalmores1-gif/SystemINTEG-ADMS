<?php
// ============================================================
// DATABASE CONNECTION
// File: production/db.php
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clinic_inventory');

date_default_timezone_set('Asia/Manila');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('<div style="font-family:sans-serif;padding:40px;color:red;">
        <h2>Database Connection Failed</h2>
        <p>' . mysqli_connect_error() . '</p>
        <p>Make sure XAMPP is running and the <strong>clinic_inventory</strong> database exists.</p>
        <p>Import <code>clinic_inventory.sql</code> using phpMyAdmin first.</p>
    </div>');
}

mysqli_set_charset($conn, 'utf8mb4');
?>
