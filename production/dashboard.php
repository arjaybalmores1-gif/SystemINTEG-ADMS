<?php
// ============================================================
// DASHBOARD
// File: production/dashboard.php
// Controller pattern: same style as page1.php
// ============================================================
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$breadcrumb = 'Home > Dashboard';
require_once('_head.php');
require_once('db.php');

// ── FETCH LIVE STATS FROM DATABASE ───────────────────────────
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM inventory");
$totalItems = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COALESCE(SUM(quantity),0) AS c FROM inventory");
$totalStock = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM inventory WHERE quantity > 0 AND quantity <= low_stock_level");
$lowCount = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM inventory WHERE quantity = 0");
$outCount = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM inventory WHERE expiration_date IS NOT NULL AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$expCount = mysqli_fetch_assoc($r)['c'];

// Item type breakdown
$r = mysqli_query($conn, "SELECT item_type, COUNT(*) AS cnt FROM inventory GROUP BY item_type");
$typeBreak = ['Medicine' => 0, 'Medical Supply' => 0];
while ($row = mysqli_fetch_assoc($r)) $typeBreak[$row['item_type']] = $row['cnt'];

// Recent 10 transactions
$recentTx = mysqli_query($conn,
    "SELECT t.transaction_type, t.quantity, t.transaction_date,
            i.item_name, u.full_name
     FROM inventory_transactions t
     JOIN inventory i ON t.inventory_id = i.id
     JOIN users u ON t.user_id = u.id
     ORDER BY t.transaction_date DESC LIMIT 10");

// Top 5 low-stock items
$lowItems = mysqli_query($conn,
    "SELECT item_name, quantity, low_stock_level, unit
     FROM inventory WHERE quantity > 0 AND quantity <= low_stock_level
     ORDER BY quantity ASC LIMIT 5");

// Top 5 expiring items
$expItems = mysqli_query($conn,
    "SELECT item_name, quantity, unit, expiration_date
     FROM inventory WHERE expiration_date IS NOT NULL
       AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY expiration_date ASC LIMIT 5");

require_once('_sidebar.php');
?>

<main id="main-content" tabindex="-1" class="main">
<div class="page-wrapper">

  <div class="page-header">
    <div class="page-header-row">
      <div>
        <div class="page-pretitle">Overview</div>
        <h1 class="page-title">Dashboard</h1>
      </div>
      <div class="page-actions">
        <a class="btn btn-outline" href="reports.php">
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 12h2V8H2v4zm4 0h2V4H6v8zm4 0h2V6h-2v6z"/></svg>
          View Reports
        </a>
        <a class="btn btn-primary" href="inventory.php?action=add">
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 8h8M8 4v8"/></svg>
          Add Item
        </a>
      </div>
    </div>
  </div>

  <?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
    <div class="alert-msg alert-error">✗ Access denied. You do not have permission to view that page.</div>
  <?php endif; ?>

  <!-- STAT CARDS ROW 1 -->
  <div class="row col-3">
    <div class="card">
      <div class="stat">
        <div class="stat-icon teal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 3H8a2 2 0 00-2 4h12a2 2 0 00-2-4z"/></svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Items</div>
          <div class="stat-value-row"><span class="stat-value"><?php echo number_format($totalItems); ?></span></div>
          <div class="stat-subtext"><?php echo $typeBreak['Medicine']; ?> medicines · <?php echo $typeBreak['Medical Supply']; ?> supplies</div>
        </div>
        <div class="stat-spark">
          <div class="bar" style="height:60%"></div><div class="bar" style="height:65%"></div><div class="bar" style="height:70%"></div><div class="bar" style="height:75%"></div><div class="bar" style="height:80%"></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="stat">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 3v18"/></svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Stock Units</div>
          <div class="stat-value-row"><span class="stat-value"><?php echo number_format($totalStock); ?></span></div>
          <div class="stat-subtext">Combined available quantity</div>
        </div>
        <div class="stat-spark">
          <div class="bar" style="height:50%;background:var(--blue)"></div><div class="bar" style="height:60%;background:var(--blue)"></div><div class="bar" style="height:70%;background:var(--blue)"></div><div class="bar" style="height:65%;background:var(--blue)"></div><div class="bar" style="height:80%;background:var(--blue)"></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="stat">
        <div class="stat-icon yellow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Low Stock</div>
          <div class="stat-value-row"><span class="stat-value"><?php echo number_format($lowCount); ?></span></div>
          <div class="stat-subtext">Items below threshold</div>
        </div>
      </div>
      <div style="padding:0 16px 12px">
        <div class="progress-thin"><div class="bar" style="width:<?php echo $totalItems > 0 ? min(100,round(($lowCount/$totalItems)*100)) : 0; ?>%;background:var(--yellow)"></div></div>
      </div>
    </div>
  </div>

  <!-- STAT CARDS ROW 2 -->
  <div class="row col-3">
    <div class="card">
      <div class="stat">
        <div class="stat-icon red">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Out of Stock</div>
          <div class="stat-value-row"><span class="stat-value"><?php echo number_format($outCount); ?></span></div>
          <div class="stat-subtext">Items with zero quantity</div>
        </div>
      </div>
      <div style="padding:0 16px 12px">
        <div class="progress-thin"><div class="bar" style="width:<?php echo $totalItems > 0 ? min(100,round(($outCount/$totalItems)*100)) : 0; ?>%;background:var(--red)"></div></div>
      </div>
    </div>

    <div class="card">
      <div class="stat">
        <div class="stat-icon purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Expiring (30 days)</div>
          <div class="stat-value-row"><span class="stat-value"><?php echo number_format($expCount); ?></span></div>
          <div class="stat-subtext">Items expiring soon</div>
        </div>
      </div>
      <div style="padding:0 16px 12px">
        <div class="progress-thin"><div class="bar" style="width:<?php echo min(100,$expCount*10); ?>%;background:var(--purple)"></div></div>
      </div>
    </div>

    <div class="card">
      <div class="stat">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2v20M2 12h20"/></svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Quick Actions</div>
          <div style="display:flex;flex-direction:column;gap:6px;margin-top:8px;">
            <a href="stock_in.php" class="btn btn-outline" style="font-size:12px;height:28px;padding:0 12px;">↓ Stock In</a>
            <a href="stock_out.php" class="btn btn-outline" style="font-size:12px;height:28px;padding:0 12px;color:var(--red);border-color:var(--red);">↑ Stock Out</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- BOTTOM ROW -->
  <div class="row col-8-4">

    <!-- Recent Transactions -->
    <div class="card">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <div class="card-title">Recent Transactions</div>
        <a href="transactions.php" style="font-size:12.5px;color:var(--teal);">View all →</a>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="table" style="font-size:13px;">
          <thead>
            <tr><th>Item</th><th>Type</th><th>Qty</th><th>Date</th><th>By</th></tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($recentTx) > 0): ?>
              <?php while ($t = mysqli_fetch_assoc($recentTx)): ?>
                <tr>
                  <td><?php echo htmlspecialchars($t['item_name']); ?></td>
                  <td>
                    <?php if ($t['transaction_type'] === 'Stock In'): ?>
                      <span class="badge-stockin">Stock In</span>
                    <?php else: ?>
                      <span class="badge-stockout">Stock Out</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo number_format($t['quantity']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($t['transaction_date'])); ?></td>
                  <td><?php echo htmlspecialchars($t['full_name']); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:20px;">No transactions yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Alert Panels -->
    <div style="display:flex;flex-direction:column;gap:16px;">

      <div class="card">
        <div class="card-header"><div class="card-title" style="color:var(--yellow,#f39c12);">⚠ Low Stock</div></div>
        <div class="card-body" style="padding:8px 16px;">
          <?php if (mysqli_num_rows($lowItems) > 0): ?>
            <?php while ($item = mysqli_fetch_assoc($lowItems)): ?>
              <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-color);font-size:13px;">
                <div>
                  <div style="font-weight:500;"><?php echo htmlspecialchars($item['item_name']); ?></div>
                  <div style="font-size:11.5px;color:var(--text-muted);">Min: <?php echo $item['low_stock_level'].' '.$item['unit']; ?></div>
                </div>
                <span class="badge-low"><?php echo $item['quantity']; ?> left</span>
              </div>
            <?php endwhile; ?>
            <a href="reports.php?filter=low_stock" style="display:block;text-align:center;font-size:12px;margin-top:8px;color:var(--teal);">View all →</a>
          <?php else: ?>
            <p style="font-size:13px;color:var(--text-muted);text-align:center;padding:8px 0;">All stock levels OK.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title" style="color:var(--purple,#9b59b6);">📅 Expiring Soon</div></div>
        <div class="card-body" style="padding:8px 16px;">
          <?php if (mysqli_num_rows($expItems) > 0): ?>
            <?php while ($item = mysqli_fetch_assoc($expItems)): ?>
              <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-color);font-size:13px;">
                <div style="font-weight:500;"><?php echo htmlspecialchars($item['item_name']); ?></div>
                <span class="badge-low"><?php echo date('M d, Y', strtotime($item['expiration_date'])); ?></span>
              </div>
            <?php endwhile; ?>
            <a href="reports.php?filter=expiring" style="display:block;text-align:center;font-size:12px;margin-top:8px;color:var(--teal);">View all →</a>
          <?php else: ?>
            <p style="font-size:13px;color:var(--text-muted);text-align:center;padding:8px 0;">No items expiring within 30 days.</p>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

</div>
</main>

<script type="module" src="../js/rolldown-runtime-_5RX-BWT.js"></script>
<script type="module" src="../js/toast-CBtjS_PZ.js"></script>
<script type="module" src="../js/main-v4-5cBbCq9P.js"></script>
</body>
</html>
