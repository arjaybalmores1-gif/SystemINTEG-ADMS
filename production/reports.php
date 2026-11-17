<?php
// ============================================================
// REPORTS PAGE
// File: production/reports.php
//
// PURPOSE:
//   Displays two types of reports that the user can switch between:
//     1. Inventory Report  — current stock levels for all items
//     2. Transaction Report — history of all stock movements
//
//   Both reports support filtering and can be printed.
//
// FILTERS AVAILABLE:
//   - search      : filter by item name (partial match using LIKE)
//   - category    : filter by category ID (exact match)
//   - type        : filter by item type (Medicine / Medical Supply)
//                   OR transaction type (Stock In / Stock Out)
//   - date_from   : start date for transaction date range
//   - date_to     : end date for transaction date range
//   - filter      : quick-filter shortcuts (low_stock, out_of_stock, expiring)
//
// SQL FUNCTIONS USED (documented below each query):
//   COUNT(), SUM(), COALESCE(), CURDATE(), DATE_ADD(), INTERVAL,
//   BETWEEN, JOIN, WHERE with dynamic conditions, ORDER BY
// ============================================================

$pageTitle  = 'Reports';
$activePage = 'reports';
$breadcrumb = 'Home > Reports';
require_once('_head.php');
require_once('db.php');

// ── READ FILTER VALUES FROM URL ($_GET) ──────────────────────
$reportType = trim($_GET['report']    ?? 'inventory'); // 'inventory' or 'transactions'
$search     = trim($_GET['search']    ?? '');          // partial item name search
$catFilter  = trim($_GET['category']  ?? '');          // category ID
$typeFilter = trim($_GET['type']      ?? '');          // item type or transaction type
$dateFrom   = trim($_GET['date_from'] ?? '');          // date range start (YYYY-MM-DD)
$dateTo     = trim($_GET['date_to']   ?? '');          // date range end (YYYY-MM-DD)
$filter     = trim($_GET['filter']    ?? '');          // quick-filter: low_stock | out_of_stock | expiring

// If a quick-filter is active, force inventory report view
if (in_array($filter, ['low_stock', 'out_of_stock', 'expiring'])) {
    $reportType = 'inventory';
}

// ============================================================
// SUMMARY STATISTICS QUERIES
// These 5 queries power the stat cards at the top of the page.
// ============================================================

/*
 * COUNT(*) — counts ALL rows in the inventory table.
 * Returns total number of inventory items registered in the system.
 */
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM inventory");
$totalItems = mysqli_fetch_assoc($r)['c'];

/*
 * COALESCE(SUM(quantity), 0)
 *   SUM(quantity) — adds up the quantity column for all rows.
 *   COALESCE(value, 0) — returns 0 instead of NULL if the table is empty.
 * Returns the combined total units across all inventory items.
 */
$r = mysqli_query($conn, "SELECT COALESCE(SUM(quantity), 0) AS c FROM inventory");
$totalStock = mysqli_fetch_assoc($r)['c'];

/*
 * COUNT(*) with WHERE quantity > 0 AND quantity <= low_stock_level
 *   Finds items that still have some stock but are at or below their alert threshold.
 *   "quantity > 0" excludes fully out-of-stock items from this count.
 * Returns number of items in a "low stock" warning state.
 */
$r = mysqli_query($conn, "
    SELECT COUNT(*) AS c
    FROM inventory
    WHERE quantity > 0
      AND quantity <= low_stock_level
");
$lowCount = mysqli_fetch_assoc($r)['c'];

/*
 * COUNT(*) with WHERE quantity = 0
 *   Finds items with no remaining stock at all.
 * Returns number of completely out-of-stock items.
 */
$r = mysqli_query($conn, "
    SELECT COUNT(*) AS c
    FROM inventory
    WHERE quantity = 0
");
$outCount = mysqli_fetch_assoc($r)['c'];

/*
 * COUNT(*) with BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
 *   CURDATE()       — returns today's date (e.g. 2026-11-17)
 *   DATE_ADD(date, INTERVAL 30 DAY) — adds 30 days to a date
 *   BETWEEN x AND y — true if expiration_date falls within the next 30 days
 *   IS NOT NULL     — skips items with no expiration date (medical supplies, etc.)
 * Returns number of items expiring within the next 30 days.
 */
$r = mysqli_query($conn, "
    SELECT COUNT(*) AS c
    FROM inventory
    WHERE expiration_date IS NOT NULL
      AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");
$expCount = mysqli_fetch_assoc($r)['c'];

// ── LOAD CATEGORIES FOR FILTER DROPDOWN ─────────────────────
$catResult = mysqli_query($conn, "SELECT id, category_name FROM categories ORDER BY category_name ASC");
$catOpts = '<option value="">All Categories</option>';
while ($c = mysqli_fetch_assoc($catResult)) {
    $sel      = ($catFilter == $c['id']) ? 'selected' : '';
    $catOpts .= "<option value=\"{$c['id']}\" $sel>" . htmlspecialchars($c['category_name']) . "</option>";
}

// ============================================================
// INVENTORY REPORT QUERY
//
// Builds a dynamic WHERE clause based on active filters.
// Uses prepared statements with ? placeholders to prevent
// SQL injection — values are bound separately via bind_param().
//
// SQL JOINS used:
//   inventory JOIN categories ON i.category_id = c.id
//     — pulls category_name alongside each inventory row.
//
// SQL FUNCTIONS used:
//   LIKE '%value%' — partial text match for item name search
//   ORDER BY item_name ASC — alphabetical sort
// ============================================================

$invWhere  = "WHERE 1=1"; // "1=1" is always true, allows easy AND appending
$invParams = [];           // values to bind to the ? placeholders
$invTypes  = '';           // type string for mysqli_stmt_bind_param: s=string, i=integer

// Filter: item name partial search
if ($search !== '') {
    $invWhere   .= " AND i.item_name LIKE ?";
    $invParams[] = "%$search%"; // % wildcards = match anywhere in the name
    $invTypes   .= 's';
}

// Filter: specific category ID (exact match)
if ($catFilter !== '') {
    $invWhere   .= " AND i.category_id = ?";
    $invParams[] = (int)$catFilter; // cast to integer for safety
    $invTypes   .= 'i';
}

// Filter: item type (Medicine or Medical Supply)
if ($typeFilter !== '') {
    $invWhere   .= " AND i.item_type = ?";
    $invParams[] = $typeFilter;
    $invTypes   .= 's';
}

// Quick-filter: items that are low on stock
if ($filter === 'low_stock') {
    $invWhere .= " AND i.quantity > 0 AND i.quantity <= i.low_stock_level";
}

// Quick-filter: items with zero quantity
if ($filter === 'out_of_stock') {
    $invWhere .= " AND i.quantity = 0";
}

// Quick-filter: items expiring within 30 days
// CURDATE() = today | DATE_ADD = today + 30 days | BETWEEN = within that range
if ($filter === 'expiring') {
    $invWhere .= " AND i.expiration_date IS NOT NULL
                   AND i.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

// Build and execute the inventory query with prepared statement
$invStmt = mysqli_prepare($conn,
    "SELECT i.*, c.category_name
     FROM inventory i
     JOIN categories c ON i.category_id = c.id
     $invWhere
     ORDER BY i.item_name ASC"
);

// Only bind parameters if there are filters active
if ($invParams) {
    mysqli_stmt_bind_param($invStmt, $invTypes, ...$invParams);
}
mysqli_stmt_execute($invStmt);
$invResult = mysqli_stmt_get_result($invStmt);

// Collect rows into an array for display
$invRows = [];
while ($row = mysqli_fetch_assoc($invResult)) {
    $invRows[] = $row;
}

// ============================================================
// TRANSACTION REPORT QUERY
//
// SQL JOINS used:
//   inventory_transactions t
//     JOIN inventory i   ON t.inventory_id = i.id   — item details
//     JOIN categories c  ON i.category_id  = c.id   — category name
//     JOIN users u       ON t.user_id      = u.id   — who did the transaction
//
// SQL FUNCTIONS used:
//   DATE(t.transaction_date) — extracts only the date part (strips time)
//     so we can compare against a plain YYYY-MM-DD filter value
//   ORDER BY transaction_date DESC — newest transactions shown first
// ============================================================

$txWhere  = "WHERE 1=1";
$txParams = [];
$txTypes  = '';

// Filter: item name partial search
if ($search !== '') {
    $txWhere   .= " AND i.item_name LIKE ?";
    $txParams[] = "%$search%";
    $txTypes   .= 's';
}

// Filter: transaction type (Stock In or Stock Out)
if ($typeFilter !== '') {
    $txWhere   .= " AND t.transaction_type = ?";
    $txParams[] = $typeFilter;
    $txTypes   .= 's';
}

// Filter: date range start
// DATE(t.transaction_date) extracts just the date portion (removes the time part)
// so "2026-11-01" matches "2026-11-01 14:35:22"
if ($dateFrom !== '') {
    $txWhere   .= " AND DATE(t.transaction_date) >= ?";
    $txParams[] = $dateFrom;
    $txTypes   .= 's';
}

// Filter: date range end
if ($dateTo !== '') {
    $txWhere   .= " AND DATE(t.transaction_date) <= ?";
    $txParams[] = $dateTo;
    $txTypes   .= 's';
}

// Build and execute transaction query
$txStmt = mysqli_prepare($conn,
    "SELECT t.id, t.transaction_type, t.quantity, t.remarks, t.transaction_date,
            i.item_name, i.unit,
            c.category_name,
            u.full_name
     FROM inventory_transactions t
     JOIN inventory   i ON t.inventory_id = i.id
     JOIN categories  c ON i.category_id  = c.id
     JOIN users       u ON t.user_id      = u.id
     $txWhere
     ORDER BY t.transaction_date DESC"
);

if ($txParams) {
    mysqli_stmt_bind_param($txStmt, $txTypes, ...$txParams);
}
mysqli_stmt_execute($txStmt);
$txResult = mysqli_stmt_get_result($txStmt);

$txRows = [];
while ($row = mysqli_fetch_assoc($txResult)) {
    $txRows[] = $row;
}

// ── SELECTED STATE HELPERS FOR DROPDOWNS ─────────────────────
$selMed = $typeFilter === 'Medicine'       ? 'selected' : '';
$selSup = $typeFilter === 'Medical Supply' ? 'selected' : '';
$selSIn = $typeFilter === 'Stock In'       ? 'selected' : '';
$selSOt = $typeFilter === 'Stock Out'      ? 'selected' : '';

require_once('_sidebar.php');
?>
<main id="main-content" tabindex="-1" class="main">
<div class="page-wrapper">

  <div class="page-header">
    <div class="page-header-row">
      <div>
        <div class="page-pretitle">Reports &amp; Statistics</div>
        <h1 class="page-title">Inventory Reports</h1>
      </div>
      <div class="page-actions no-print">
        <button onclick="window.print()" class="btn btn-outline">
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M4 4V2h8v2M4 12H2V7a2 2 0 012-2h8a2 2 0 012 2v5h-2M4 10h8v4H4v-4z"/>
          </svg>
          Print
        </button>
      </div>
    </div>
  </div>

  <!-- ── SUMMARY STAT CARDS ───────────────────────────────── -->
  <!-- Values come from the 5 COUNT/SUM queries at the top -->
  <div class="row col-3" style="--col:5;">

    <div class="card">
      <div class="stat">
        <div class="stat-icon teal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 7H4a2 2 0 00-2 2v10h20V9a2 2 0 00-2-2z"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Items</div>
          <div class="stat-value-row">
            <span class="stat-value"><?php echo number_format($totalItems); ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="stat">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <path d="M3 9h18M9 3v18"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Stock Units</div>
          <div class="stat-value-row">
            <span class="stat-value"><?php echo number_format($totalStock); ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="stat">
        <div class="stat-icon yellow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Low Stock</div>
          <div class="stat-value-row">
            <span class="stat-value"><?php echo number_format($lowCount); ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="stat">
        <div class="stat-icon red">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Out of Stock</div>
          <div class="stat-value-row">
            <span class="stat-value"><?php echo number_format($outCount); ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="stat">
        <div class="stat-icon purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="4" width="18" height="18" rx="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Expiring (30 days)</div>
          <div class="stat-value-row">
            <span class="stat-value"><?php echo number_format($expCount); ?></span>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- ── QUICK FILTER PILLS ───────────────────────────────── -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;" class="no-print">
    <a href="reports.php?report=inventory"
       class="btn <?php echo ($filter === '' && $reportType === 'inventory') ? 'btn-primary' : 'btn-outline'; ?>"
       style="font-size:12.5px;">All Inventory</a>

    <a href="reports.php?report=inventory&filter=low_stock"
       class="btn <?php echo $filter === 'low_stock' ? 'btn-primary' : 'btn-outline'; ?>"
       style="font-size:12.5px;border-color:var(--yellow);color:<?php echo $filter === 'low_stock' ? '#fff' : 'var(--yellow)'; ?>;">
       Low Stock</a>

    <a href="reports.php?report=inventory&filter=out_of_stock"
       class="btn <?php echo $filter === 'out_of_stock' ? 'btn-primary' : 'btn-outline'; ?>"
       style="font-size:12.5px;border-color:var(--red);color:<?php echo $filter === 'out_of_stock' ? '#fff' : 'var(--red)'; ?>;">
       Out of Stock</a>

    <a href="reports.php?report=inventory&filter=expiring"
       class="btn <?php echo $filter === 'expiring' ? 'btn-primary' : 'btn-outline'; ?>"
       style="font-size:12.5px;border-color:var(--purple);color:<?php echo $filter === 'expiring' ? '#fff' : 'var(--purple)'; ?>;">
       Expiring Soon</a>

    <a href="reports.php?report=transactions"
       class="btn <?php echo $reportType === 'transactions' ? 'btn-primary' : 'btn-outline'; ?>"
       style="font-size:12.5px;">Transactions</a>
  </div>

  <div class="card">
    <div class="card-body">

      <!-- ── FILTER FORM ─────────────────────────────────── -->
      <form method="GET" action="reports.php" class="no-print">
        <!-- Pass current report type and quick-filter as hidden fields -->
        <input type="hidden" name="report" value="<?php echo htmlspecialchars($reportType); ?>">
        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">

        <div class="search-filter-row">
          <!-- Text search — matches item_name using LIKE -->
          <input type="text" name="search" class="form-control"
                 placeholder="Search item name..."
                 value="<?php echo htmlspecialchars($search); ?>">

          <!-- Category dropdown — matches category_id exactly -->
          <select name="category" class="form-control"><?php echo $catOpts; ?></select>

          <!-- Type filter changes depending on which report is showing -->
          <?php if ($reportType === 'inventory'): ?>
            <select name="type" class="form-control">
              <option value="">All Types</option>
              <option value="Medicine"       <?php echo $selMed; ?>>Medicine</option>
              <option value="Medical Supply" <?php echo $selSup; ?>>Medical Supply</option>
            </select>
          <?php else: ?>
            <select name="type" class="form-control">
              <option value="">All Trans. Types</option>
              <option value="Stock In"  <?php echo $selSIn; ?>>Stock In</option>
              <option value="Stock Out" <?php echo $selSOt; ?>>Stock Out</option>
            </select>
            <!-- Date range filters — only shown for transaction report -->
            <input type="date" name="date_from" class="form-control"
                   value="<?php echo htmlspecialchars($dateFrom); ?>" title="From Date">
            <input type="date" name="date_to" class="form-control"
                   value="<?php echo htmlspecialchars($dateTo); ?>" title="To Date">
          <?php endif; ?>

          <button type="submit" class="btn btn-primary" style="height:38px;">Filter</button>
          <a href="reports.php?report=<?php echo htmlspecialchars($reportType); ?>"
             class="btn btn-outline" style="height:38px;">Reset</a>
        </div>
      </form>

      <!-- Print-only header (hidden on screen, shown when printing) -->
      <div class="print-header">
        <h2 style="margin:0;">Clinic Inventory Management System</h2>
        <div>Report Type: <?php echo $reportType === 'inventory' ? 'Inventory Report' : 'Transaction Report'; ?></div>
        <div>Generated: <?php echo date('F d, Y h:i A'); ?></div>
        <hr>
      </div>
      <style>
        .print-header { display: none; }
        @media print  { .print-header { display: block !important; } }
      </style>

      <!-- ── INVENTORY REPORT TABLE ─────────────────────── -->
      <?php if ($reportType === 'inventory'): ?>

        <table class="table" style="font-size:13px;">
          <thead>
            <tr>
              <th>#</th>
              <th>Item Name</th>
              <th>Category</th>
              <th>Type</th>
              <th>Qty</th>
              <th>Unit</th>
              <th>Unit Cost</th>
              <th>Expiry Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($invRows) > 0): ?>
              <?php foreach ($invRows as $i => $row): ?>
                <?php
                  // Determine status badge based on quantity vs low_stock_level
                  if ($row['quantity'] == 0) {
                      $status = '<span class="badge-out">Out of Stock</span>';
                  } elseif ($row['quantity'] <= $row['low_stock_level']) {
                      $status = '<span class="badge-low">Low Stock</span>';
                  } else {
                      $status = '<span class="badge-ok">OK</span>';
                  }

                  $typeBadge = $row['item_type'] === 'Medicine'
                      ? '<span class="badge-medicine">Medicine</span>'
                      : '<span class="badge-supply">Supply</span>';

                  // Format expiration date — show dash if no expiry set
                  $exp = $row['expiration_date']
                      ? date('M d, Y', strtotime($row['expiration_date']))
                      : '—';
                ?>
                <tr>
                  <td><?php echo $i + 1; ?></td>
                  <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                  <td><?php echo $typeBadge; ?></td>
                  <td><?php echo number_format($row['quantity']); ?></td>
                  <td><?php echo htmlspecialchars($row['unit']); ?></td>
                  <td>₱<?php echo number_format($row['unit_cost'], 2); ?></td>
                  <td><?php echo $exp; ?></td>
                  <td><?php echo $status; ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" style="text-align:center;color:var(--text-muted);padding:24px;">
                  No inventory records found for the selected filters.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
        <div style="font-size:12.5px;color:var(--text-muted);margin-top:8px;">
          Showing <?php echo count($invRows); ?> record(s)
        </div>

      <!-- ── TRANSACTION REPORT TABLE ───────────────────── -->
      <?php else: ?>

        <table class="table" style="font-size:13px;">
          <thead>
            <tr>
              <th>#</th>
              <th>Item Name</th>
              <th>Category</th>
              <th>Trans. Type</th>
              <th>Quantity</th>
              <th>Remarks</th>
              <th>Date &amp; Time</th>
              <th>Recorded By</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($txRows) > 0): ?>
              <?php foreach ($txRows as $i => $t): ?>
                <tr>
                  <td><?php echo $i + 1; ?></td>
                  <td><?php echo htmlspecialchars($t['item_name']); ?></td>
                  <td><?php echo htmlspecialchars($t['category_name']); ?></td>
                  <td>
                    <?php if ($t['transaction_type'] === 'Stock In'): ?>
                      <span class="badge-stockin">Stock In</span>
                    <?php else: ?>
                      <span class="badge-stockout">Stock Out</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo number_format($t['quantity']) . ' ' . htmlspecialchars($t['unit']); ?></td>
                  <td><?php echo htmlspecialchars($t['remarks'] ?? '—'); ?></td>
                  <td><?php echo date('M d, Y h:i A', strtotime($t['transaction_date'])); ?></td>
                  <td><?php echo htmlspecialchars($t['full_name']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" style="text-align:center;color:var(--text-muted);padding:24px;">
                  No transactions found for the selected filters.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
        <div style="font-size:12.5px;color:var(--text-muted);margin-top:8px;">
          Showing <?php echo count($txRows); ?> record(s)
        </div>

      <?php endif; ?>

    </div>
  </div>

</div>
</main>

<script type="module" src="../js/rolldown-runtime-_5RX-BWT.js"></script>
<script type="module" src="../js/toast-CBtjS_PZ.js"></script>
<script type="module" src="../js/main-v4-5cBbCq9P.js"></script>
</body>
</html>
