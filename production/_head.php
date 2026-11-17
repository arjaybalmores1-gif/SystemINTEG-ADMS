<?php
// ============================================================
// SHARED HEAD + SESSION GUARD
// File: production/_head.php
// Usage: set $pageTitle and $activePage before including this.
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Role guard — set $requireAdmin = true on admin-only pages
if (!empty($requireAdmin) && $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

if (!isset($pageTitle))   $pageTitle   = 'Clinic IMS';
if (!isset($activePage))  $activePage  = '';
if (!isset($breadcrumb))  $breadcrumb  = 'Home';
if (!isset($requireAdmin)) $requireAdmin = false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?> | Clinic IMS</title>
<link rel="icon" href="../images/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" crossorigin href="../assets/main-v4-DB_ReeJG.css">
<script>(function(){try{var t=localStorage.getItem('theme');var d=window.matchMedia('(prefers-color-scheme: dark)').matches;var theme=t||(d?'dark':'light');document.documentElement.setAttribute('data-theme',theme);}catch(e){}})();</script>
<style>
.alert-msg{padding:10px 16px;border-radius:6px;margin-bottom:14px;font-size:13.5px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#e6f9f0;color:#1a7a4a;border-left:3px solid #1ABB9C;}
.alert-error  {background:#fff0f0;color:#b91c1c;border-left:3px solid #e74c3c;}
.alert-warning{background:#fffbeb;color:#92400e;border-left:3px solid #f39c12;}
.badge-medicine{background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:20px;font-size:11.5px;font-weight:500;}
.badge-supply  {background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:20px;font-size:11.5px;font-weight:500;}
.badge-low     {background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:20px;font-size:11.5px;font-weight:500;}
.badge-out     {background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:20px;font-size:11.5px;font-weight:500;}
.badge-ok      {background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:20px;font-size:11.5px;font-weight:500;}
.badge-stockin {background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:20px;font-size:11.5px;font-weight:500;}
.badge-stockout{background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:20px;font-size:11.5px;font-weight:500;}
.action-btns{display:flex;gap:6px;flex-wrap:wrap;}
.search-filter-row{display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;}
.search-filter-row .form-control{min-width:150px;}
@media print{.sidebar,.topbar,.page-actions,.search-filter-row,.action-btns,.no-print{display:none!important;}.main{margin:0!important;padding:0!important;}}
</style>
</head>
<body data-shell="admin">
<a class="skip-link" href="#main-content">Skip to main content</a>
