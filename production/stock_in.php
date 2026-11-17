<?php
// ============================================================
// STOCK IN
// File: production/stock_in.php
// Uses MySQL transaction for data integrity.
// ============================================================
$pageTitle  = 'Stock In';
$activePage = 'stock-in';
$breadcrumb = 'Home > Transactions > Stock In';
require_once('_head.php');
require_once('db.php');

$errorMsg   = '';
$successMsg = '';

// Load all items
$itemsResult = mysqli_query($conn,
    "SELECT i.id, i.item_name, i.quantity, i.unit, c.category_name
     FROM inventory i JOIN categories c ON i.category_id=c.id ORDER BY i.item_name ASC");

// ── PROCESS FORM ─────────────────────────────────────────────
if (isset($_POST['btnStockIn'])) {
    $inventoryId = (int)($_POST['cmbItem']     ?? 0);
    $qty         = (int)($_POST['txtQuantity'] ?? 0);
    $remarks     = trim($_POST['txtRemarks']   ?? '');
    $userId      = $_SESSION['user_id'];

    if ($inventoryId <= 0)   $errorMsg = 'Please select an item.';
    elseif ($qty <= 0)       $errorMsg = 'Quantity must be greater than zero.';
    else {
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn,"UPDATE inventory SET quantity=quantity+? WHERE id=?");
            mysqli_stmt_bind_param($stmt,'ii',$qty,$inventoryId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $type = 'Stock In';
            $stmt = mysqli_prepare($conn,
                "INSERT INTO inventory_transactions (inventory_id,user_id,transaction_type,quantity,remarks) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt,'iissi',$inventoryId,$userId,$type,$qty,$remarks);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);
            $successMsg = "Stock In recorded. Quantity increased by $qty.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errorMsg = 'Transaction failed. Please try again.';
        }
    }
}

// Build JSON map for JS live stock display
$stockMap = [];
$r2 = mysqli_query($conn,"SELECT id, quantity, unit FROM inventory ORDER BY id");
while ($row = mysqli_fetch_assoc($r2)) $stockMap[$row['id']] = ['qty'=>$row['quantity'],'unit'=>$row['unit']];
$stockMapJson = json_encode($stockMap);

// Build item <option> list
$itemOptions = '<option value="">— Select Item —</option>';
while ($row = mysqli_fetch_assoc($itemsResult)) {
    $sel = (($_POST['cmbItem'] ?? 0) == $row['id']) ? 'selected' : '';
    $label = htmlspecialchars($row['item_name']).' ['.htmlspecialchars($row['category_name']).'] — Stock: '.$row['quantity'].' '.htmlspecialchars($row['unit']);
    $itemOptions .= "<option value=\"{$row['id']}\" $sel>$label</option>";
}

require_once('_sidebar.php');
?>
<main id="main-content" tabindex="-1" class="main">
<div class="page-wrapper">

  <div class="page-header">
    <div class="page-header-row">
      <div><div class="page-pretitle">Transactions</div><h1 class="page-title">Stock In</h1></div>
      <div class="page-actions">
        <a href="transactions.php" class="btn btn-outline">View History</a>
        <a href="stock_out.php" class="btn btn-outline" style="color:var(--red);border-color:var(--red);">Stock Out</a>
      </div>
    </div>
  </div>

  <?php if ($successMsg): ?>
    <div class="alert-msg alert-success">✓ <?php echo htmlspecialchars($successMsg); ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert-msg alert-error">✗ <?php echo htmlspecialchars($errorMsg); ?></div>
  <?php endif; ?>

  <div class="row col-8-4" style="align-items:flex-start;">

    <div class="card">
      <div class="card-header"><div class="card-title">Record Stock In</div></div>
      <div class="card-body">
        <form method="POST" action="stock_in.php">

          <div class="form-group">
            <label class="form-label">Select Item <span style="color:red">*</span></label>
            <select name="cmbItem" class="form-control" id="itemSelect" required>
              <?php echo $itemOptions; ?>
            </select>
          </div>

          <div id="stockBox" style="background:var(--bg-secondary,#f8f9fa);border-radius:6px;padding:10px 14px;margin-bottom:14px;font-size:13px;display:none;">
            Current Stock: <strong id="stockVal">—</strong>
          </div>

          <div class="form-group">
            <label class="form-label">Quantity to Add <span style="color:red">*</span></label>
            <input type="number" name="txtQuantity" class="form-control" min="1" placeholder="Enter quantity"
                   value="<?php echo htmlspecialchars($_POST['txtQuantity'] ?? ''); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Remarks / Notes</label>
            <textarea name="txtRemarks" class="form-control" rows="3"
                      placeholder="e.g. Purchased from supplier..."><?php echo htmlspecialchars($_POST['txtRemarks'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Date &amp; Time (Auto)</label>
            <input type="text" class="form-control" value="<?php echo date('F d, Y h:i A'); ?>" readonly
                   style="background:var(--bg-secondary,#f8f9fa);color:var(--text-muted);">
          </div>

          <div style="display:flex;gap:8px;">
            <button type="submit" name="btnStockIn" class="btn btn-primary" style="flex:1;justify-content:center;">
              ↓ Record Stock In
            </button>
            <a href="stock_in.php" class="btn btn-outline" style="flex:1;justify-content:center;">Reset</a>
          </div>

        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">How it works</div></div>
      <div class="card-body" style="font-size:13px;line-height:1.7;">
        <p>Use this to record newly purchased, restocked, or donated items added to the clinic.</p>
        <ul style="padding-left:18px;margin-top:8px;">
          <li>Select the item.</li>
          <li>Enter quantity being added.</li>
          <li>Add optional remarks.</li>
          <li>Click <strong>Record Stock In</strong>.</li>
        </ul>
        <p style="margin-top:12px;color:var(--text-muted);">Stock quantity is automatically increased and a transaction log is created.</p>
      </div>
      <div class="card-footer">
        <a href="inventory.php" class="btn btn-outline" style="font-size:12.5px;">View Inventory List</a>
      </div>
    </div>

  </div>
</div>
</main>

<script>
(function(){
  const stockMap = <?php echo $stockMapJson; ?>;
  const sel = document.getElementById('itemSelect');
  const box = document.getElementById('stockBox');
  const val = document.getElementById('stockVal');
  function update(){
    const id = sel.value;
    if(id && stockMap[id]){
      val.textContent = stockMap[id].qty + ' ' + stockMap[id].unit;
      box.style.display = 'block';
    } else { box.style.display = 'none'; }
  }
  sel.addEventListener('change', update);
  update();
})();
</script>

<script type="module" src="../js/rolldown-runtime-_5RX-BWT.js"></script>
<script type="module" src="../js/toast-CBtjS_PZ.js"></script>
<script type="module" src="../js/main-v4-5cBbCq9P.js"></script>
</body>
</html>
