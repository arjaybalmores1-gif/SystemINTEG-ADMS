<?php
// ============================================================
// SHARED SIDEBAR + TOPBAR
// File: production/_sidebar.php
//
// HOW TO USE:
//   Set $activePage and $breadcrumb BEFORE including this file.
//
//   $activePage values:
//     'dashboard' | 'inventory' | 'categories' |
//     'stock-in'  | 'stock-out' | 'transactions' |
//     'reports'   | 'users'
//
//   $breadcrumb example: 'Home > Inventory > Items List'
// ============================================================

// Safe defaults so no undefined variable warnings
if (!isset($activePage)) $activePage = '';
if (!isset($breadcrumb)) $breadcrumb = 'Home';

// Logged-in user info for sidebar footer
$sUser    = htmlspecialchars($_SESSION['full_name'] ?? 'User');
$sRole    = ucfirst($_SESSION['role'] ?? '');
$sInitial = strtoupper(substr($sUser, 0, 1));

// Which nav item gets the "active" highlight
function navActive($page, $current) {
    return $page === $current ? ' active" aria-current="page' : '';
}
?>

<!-- ============================================================
     SIDEBAR
     Simple flat navigation — no nested dropdowns.
     Each link is a direct <a> tag for clarity.
============================================================ -->
<aside class="sidebar" aria-label="Primary navigation">

  <!-- Brand / Logo -->
  <div class="sidebar-brand">
    <div class="brand-icon" style="background:#1ABB9C;color:#fff;display:flex;align-items:center;justify-content:center;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <path d="M12 8v8M8 12h8"/>
      </svg>
    </div>
    <div class="brand-name">Clinic IMS <small>v1.0</small></div>
  </div>

  <nav class="sidebar-nav">

    <!-- ── GENERAL ─────────────────────────────────────────── -->
    <div class="nav-group">
      <div class="nav-label">General</div>

      <a class="nav-link<?php echo navActive('dashboard', $activePage); ?>" href="dashboard.php">
        <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <rect x="3" y="3" width="7" height="7" rx="1.5"/>
          <rect x="14" y="3" width="7" height="4" rx="1.5"/>
          <rect x="3" y="14" width="7" height="7" rx="1.5"/>
          <rect x="14" y="10" width="7" height="11" rx="1.5"/>
        </svg>
        <span class="nav-text">Dashboard</span>
      </a>
    </div>

    <!-- ── INVENTORY ────────────────────────────────────────── -->
    <div class="nav-group">
      <div class="nav-label">Inventory</div>

      <a class="nav-link<?php echo navActive('inventory', $activePage); ?>" href="inventory.php">
        <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
          <path d="M16 3H8a2 2 0 00-2 4h12a2 2 0 00-2-4z"/>
        </svg>
        <span class="nav-text">Items List</span>
      </a>

      <a class="nav-link<?php echo navActive('categories', $activePage); ?>" href="categories.php">
        <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <rect x="3" y="3" width="7" height="7" rx="1.5"/>
          <rect x="14" y="3" width="7" height="7" rx="1.5"/>
          <rect x="3" y="14" width="7" height="7" rx="1.5"/>
          <rect x="14" y="14" width="7" height="7" rx="1.5"/>
        </svg>
        <span class="nav-text">Categories</span>
      </a>
    </div>

    <!-- ── TRANSACTIONS ─────────────────────────────────────── -->
    <div class="nav-group">
      <div class="nav-label">Transactions</div>

      <a class="nav-link<?php echo navActive('stock-in', $activePage); ?>" href="stock_in.php">
        <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M12 3v14M5 10l7 7 7-7"/>
        </svg>
        <span class="nav-text">Stock In</span>
      </a>

      <a class="nav-link<?php echo navActive('stock-out', $activePage); ?>" href="stock_out.php">
        <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M12 21V7M5 14l7-7 7 7"/>
        </svg>
        <span class="nav-text">Stock Out</span>
      </a>

      <a class="nav-link<?php echo navActive('transactions', $activePage); ?>" href="transactions.php">
        <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
          <rect x="9" y="3" width="6" height="4" rx="1"/>
          <path d="M9 12h6M9 16h4"/>
        </svg>
        <span class="nav-text">History</span>
      </a>
    </div>

    <!-- ── REPORTS ──────────────────────────────────────────── -->
    <div class="nav-group">
      <div class="nav-label">Reports</div>

      <a class="nav-link<?php echo navActive('reports', $activePage); ?>" href="reports.php">
        <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M4 19V5M8 19v-8M12 19V9M16 19v-5M20 19v-9"/>
        </svg>
        <span class="nav-text">Reports</span>
      </a>
    </div>

    <!-- ── ADMINISTRATION (Admin only) ──────────────────────── -->
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
    <div class="nav-group">
      <div class="nav-label">Administration</div>

      <a class="nav-link<?php echo navActive('users', $activePage); ?>" href="users.php">
        <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
        <span class="nav-text">User Accounts</span>
      </a>
    </div>
    <?php endif; ?>

  </nav>

  <!-- Sidebar footer: logged-in user info + logout -->
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar"><?php echo $sInitial; ?><span class="online"></span></div>
      <div class="sidebar-user-info">
        <div class="name"><?php echo $sUser; ?></div>
        <div class="role"><?php echo $sRole; ?></div>
      </div>
      <a href="logout.php" title="Logout" style="color:var(--text-muted);padding:4px 6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
      </a>
    </div>
  </div>

</aside>

<!-- ============================================================
     TOPBAR
     Shows breadcrumb, theme toggle, logged-in user, logout link.
============================================================ -->
<header class="topbar">
  <div class="topbar-left">

    <!-- Mobile hamburger button -->
    <button class="sidebar-toggle" type="button" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>

    <!-- Breadcrumb — splits $breadcrumb by ">" -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <?php
        $parts = array_map('trim', explode('>', $breadcrumb));
        foreach ($parts as $i => $part):
            $isLast = ($i === count($parts) - 1);
      ?>
        <?php if ($isLast): ?>
          <span class="current" aria-current="page"><?php echo htmlspecialchars($part); ?></span>
        <?php else: ?>
          <span><?php echo htmlspecialchars($part); ?></span>
          <span class="sep" aria-hidden="true">›</span>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

  </div>

  <div class="topbar-right">

    <!-- Dark / light theme toggle -->
    <button class="tb-btn theme-toggle" type="button" title="Toggle theme" aria-label="Toggle theme" aria-pressed="false">
      <svg class="theme-icon-light" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="12" cy="12" r="4"/>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
      </svg>
      <svg class="theme-icon-dark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
      </svg>
    </button>

    <!-- Logged-in user display -->
    <span style="font-size:13px;color:var(--text-muted);"><?php echo $sUser; ?></span>

    <!-- Logout button -->
    <a href="logout.php" class="tb-btn" title="Logout" style="color:var(--text-muted);">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
    </a>

  </div>
</header>
