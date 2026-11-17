<?php
// ============================================================
// TRANSACTION HISTORY
// File: production/transactions.php
// ============================================================
$pageTitle  = 'Transaction History';
$activePage = 'transactions';
$breadcrumb = 'Home > Transactions > History';
require_once('_head.php');
require_once('db.php');

$search    = trim($_GET['search']    ?? '');
$typeFilter= trim($_GET['type']      ?? '');
$dateFrom  = trim($_GET['date_from'] ?? '');
$dateTo    = trim($_GET['date_to']   ?? '');

$where  = "WHERE 1=1";
$params = [];
$types  = '';

if ($search !== '')    { $where .= " AND i.item_name LIKE ?"; $params[] = "%$search%"; $types .= 's'; }
if ($typeFilter !== '') { $where .= " AND t.transaction_type=?";  $params[] = $typeFilter; $types .= 's'; }
if ($dateFrom !== '')  { $where .= " AND DATE(t.transaction_date)>=?"; $params[] = $dateFrom; $types .= 's'; }
if ($dateTo !== '')    { $where .= " AND DATE(t.transaction_date)<=?"; $params[] = $dateTo;   $types .= 's'; }

$stmt = mysqli_prepare($conn,
    "SELECT t.id,i.item_name,c.category_name,t.transaction_type,t.quantity,i.unit,t.remarks,t.transaction_date,u.full_name
     FROM inventory_transactions t
     JOIN inventory i ON t.inventory_id=i.id
     JOIN categories c ON i.category_id=c.id
     JOIN users u ON t.user_id=u.id
     $where ORDER BY t.transaction_date DESC");
if ($params) mysqli_stmt_bind_param($stmt,$types,...$params);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$rows    = [];
$inCount = $outCount = $inQty = $outQty = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
    if ($row['transaction_type'] === 'Stock In')  { $inCount++;  $inQty  += $row['quantity']; }
    if ($row['transaction_type'] === 'Stock Out') { $outCount++; $outQty += $row['quantity']; }
}
$numRows = count($rows);

$selIn  = $typeFilter === 'Stock In'  ? 'selected' : '';
$selOut = $typeFilter === 'Stock Out' ? 'selected' : '';

require_once('_sidebar.php');
?>
<main id="main-content" tabindex="-1" class="main">
<div class="page-wrapper">

  <div class="page-header">
    <div class="page-header-row">
      <div><div class="page-pretitle">Transactions</div><h1 class="page-title">Transaction History</h1></div>
      <div class="page-actions">
        <a href="stock_in.php"  class="btn btn-outline">+ Stock In</a>
        <a href="stock_out.php" class="btn btn-outline" style="color:var(--red);border-color:var(--red);">- Stock Out</a>
      </div>
    </div>
  </div>

  <!-- Summary cards -->
  <div class="row col-3">
    <div class="card"><div class="stat">
      <div class="stat-icon teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19V5M8 19v-8M12 19V9M16 19v-5M20 19v-9"/></svg></div>
      <div class="stat-content"><div class="stat-label">Total Shown</div><div class="stat-value-row"><span class="stat-value"><?php echo number_format($numRows); ?></span></div></div>
    </div></div>
    <div class="card"><div class="stat">
      <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 13V3M3 8l5-5 5 5"/></svg></div>
      <div class="stat-content"><div class="stat-label">Stock In</div><div class="stat-value-row"><span class="stat-value"><?php echo number_format($inCount); ?></span></div><div class="stat-subtext"><?php echo number_format($inQty); ?> units received</div></div>
    </div></div>
    <div class="card"><div class="stat">
      <div class="stat-icon red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 3v10M3 8l5 5 5-5"/></svg></div>
      <div class="stat-content"><div class="stat-label">Stock Out</div><div class="stat-value-row"><span class="stat-value"><?php echo number_format($outCount); ?></span></div><div class="stat-subtext"><?php echo number_format($outQty); ?> units issued</div></div>
    </div></div>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="GET" action="transactions.php">
        <div class="search-filter-row">
          <input type="text" name="search" class="form-control" placeholder="Search item name..." value="<?php echo htmlspecialchars($search); ?>">
          <select name="type" class="form-control">
            <option value="">All Types</option>
            <option value="Stock In"  <?php echo $selIn; ?>>Stock In</option>
            <option value="Stock Out" <?php echo $selOut; ?>>Stock Out</option>
          </select>
          <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>" title="From Date">
          <input type="date" name="date_to"   class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>"   title="To Date">
          <button type="submit" class="btn btn-primary" style="height:38px;">Filter</button>
          <a href="transactions.php" class="btn btn-outline" style="height:38px;">Reset</a>
        </div>
      </form>

      <table class="table" style="font-size:13px;">
        <thead>
          <tr><th>#</th><th>Item Name</th><th>Category</th><th>Type</th><th>Qty</th><th>Unit</th><th>Remarks</th><th>Date &amp; Time</th><th>Recorded By</th></tr>
        </thead>
        <tbody>
          <?php if ($numRows > 0): ?>
            <?php foreach ($rows as $i => $t): ?>
              <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo htmlspecialchars($t['item_name']); ?></td>
                <td><?php echo htmlspecialchars($t['category_name']); ?></td>
                <td>
                  <?php if ($t['transaction_type']==='Stock In'): ?>
                    <span class="badge-stockin">Stock In</span>
                  <?php else: ?>
                    <span class="badge-stockout">Stock Out</span>
                  <?php endif; ?>
                </td>
                <td><?php echo number_format($t['quantity']); ?></td>
                <td><?php echo htmlspecialchars($t['unit']); ?></td>
                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($t['remarks']??''); ?>">
                  <?php echo htmlspecialchars($t['remarks'] ?? '—'); ?>
                </td>
                <td><?php echo date('M d, Y h:i A', strtotime($t['transaction_date'])); ?></td>
                <td><?php echo htmlspecialchars($t['full_name']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:24px;">No transactions found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      <div style="font-size:12.5px;color:var(--text-muted);margin-top:8px;">Showing <?php echo number_format($numRows); ?> record(s)</div>
    </div>
  </div>

</div>
</main>
<script type="module" src="../js/rolldown-runtime-_5RX-BWT.js"></script>
<script type="module" src="../js/toast-CBtjS_PZ.js"></script>
<script type="module" src="../js/main-v4-5cBbCq9P.js"></script>
</body>
</html>
