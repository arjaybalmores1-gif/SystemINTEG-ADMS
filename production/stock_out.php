<?php
// ============================================================
// STOCK OUT
// File: production/stock_out.php
// Prevents negative stock. Uses MySQL transaction.
// ============================================================
$pageTitle  = 'Stock Out';
$activePage = 'stock-out';
$breadcrumb = 'Home > Transactions > Stock Out';
require_once('_head.php');
require_once('db.php');

$errorMsg   = '';
$successMsg = '';

$itemsResult = mysqli_query($conn,
    "SELECT i.id, i.item_name, i.quantity, i.unit, c.category_name
     FROM inventory i JOIN categories c ON i.category_id=c.id ORDER BY i.item_name ASC");

if (isset($_POST['btnStockOut'])) {
    $inventoryId = (int)($_POST['cmbItem']     ?? 0);
    $qty         = (int)($_POST['txtQuantity'] ?? 0);
    $remarks     = trim($_POST['txtRemarks']   ?? '');
    $userId      = $_SESSION['user_id'];

    if ($inventoryId <= 0) {
        $errorMsg = 'Please select an item.';
    } elseif ($qty <= 0) {
        $errorMsg = 'Quantity must be greater than zero.';
    } else {
        // Check current stock
        $cs = mysqli_prepare($conn,"SELECT quantity, item_name, unit FROM inventory WHERE id=?");
        mysqli_stmt_bind_param($cs,'i',$inventoryId);
        mysqli_stmt_execute($cs);
        $cur = mysqli_fetch_assoc(mysqli_stmt_get_result($cs));
        mysqli_stmt_close($cs);

        if (!$cur) {
            $errorMsg = 'Item not found.';
        } elseif ($qty > $cur['quantity']) {
            $errorMsg = "Insufficient stock. Available: {$cur['quantity']} {$cur['unit']}. Cannot issue more than available.";
        } else {
            mysqli_begin_transaction($conn);
            try {
                $stmt = mysqli_prepare($conn,"UPDATE inventory SET quantity=quantity-? WHERE id=? AND quantity>=?");
                mysqli_stmt_bind_param($stmt,'iii',$qty,$inventoryId,$qty);
                mysqli_stmt_execute($stmt);
                $aff = mysqli_stmt_affected_rows($conn);
                mysqli_stmt_close($stmt);

                if ($aff === 0) throw new Exception('Stock update failed.');

                $type = 'Stock Out';
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO inventory_transactions (inventory_id,user_id,transaction_type,quantity,remarks) VALUES (?,?,?,?,?)");
                mysqli_stmt_bind_param($stmt,'iissi',$inventoryId,$userId,$type,$qty,$remarks);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                mysqli_commit($conn);
                $successMsg = "Stock Out recorded. Quantity decreased by $qty.";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errorMsg = 'Transaction failed: '.$e->getMessage();
            }
        }
    }
}

$stockMap = [];
$r2 = mysqli_query($conn,"SELECT id,quantity,unit,low_stock_level FROM inventory ORDER BY id");
while ($row = mysqli_fetch_assoc($r2)) $stockMap[$row['id']] = ['qty'=>$row['quantity'],'unit'=>$row['unit'],'low'=>$row['low_stock_level']];
$stockMapJson = json_encode($stockMap);

$itemOptions = '<option value="">— Select Item —</option>';
while ($row = mysqli_fetch_assoc($itemsResult)) {
    $sel   = (($_POST['cmbItem'] ?? 0) == $row['id']) ? 'selected' : '';
    $note  = $row['quantity'] == 0 ? ' ⚠ OUT OF STOCK' : '';
    $label = htmlspecialchars($row['item_name']).' ['.htmlspecialchars($row['category_name']).'] — Stock: '.$row['quantity'].' '.htmlspecialchars($row['unit']).$note;
    $itemOptions .= "<option value=\"{$row['id']}\" $sel>$label</option>";
}

require_once('_sidebar.php');
?>
<main id="main-content" tabindex="-1" class="main">
<div class="page-wrapper">

  <div class="page-header">
    <div class="page-header-row">
      <div><div class="page-pretitle">Transactions</div><h1 class="page-title">Stock Out</h1></div>
      <div class="page-actions">
        <a href="transactions.php" class="btn btn-outline">View History</a>
        <a href="stock_in.php" class="btn btn-outline">Stock In</a>
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
      <div class="card-header"><div class="card-title">Record Stock Out</div></div>
      <div class="card-body">
        <form method="POST" action="stock_out.php">

          <div class="form-group">
            <label class="form-label">Select Item <span style="color:red">*</span></label>
            <select name="cmbItem" class="form-control" id="itemSelect" required>
              <?php echo $itemOptions; ?>
            </select>
          </div>

          <div id="stockBox" style="border-radius:6px;padding:10px 14px;margin-bottom:14px;font-size:13px;display:none;background:var(--bg-secondary,#f8f9fa);">
            Available Stock: <strong id="stockVal">—</strong>
            <span id="lowWarn" style="color:var(--red);margin-left:8px;display:none;">⚠ Low stock!</span>
          </div>

          <div class="form-group">
            <label class="form-label">Quantity to Issue <span style="color:red">*</span></label>
            <input type="number" name="txtQuantity" id="qtyInput" class="form-control" min="1"
                   value="<?php echo htmlspecialchars($_POST['txtQuantity'] ?? ''); ?>" required>
            <div id="stockErr" style="color:var(--red);font-size:12.5px;margin-top:4px;display:none;">
              Quantity exceeds available stock!
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Remarks / Purpose <span style="color:red">*</span></label>
            <textarea name="txtRemarks" class="form-control" rows="3"
                      placeholder="e.g. Dispensed to patient..."><?php echo htmlspecialchars($_POST['txtRemarks'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Date &amp; Time (Auto)</label>
            <input type="text" class="form-control" value="<?php echo date('F d, Y h:i A'); ?>" readonly
                   style="background:var(--bg-secondary,#f8f9fa);color:var(--text-muted);">
          </div>

          <div style="display:flex;gap:8px;">
            <button type="submit" name="btnStockOut" class="btn btn-primary"
                    style="flex:1;justify-content:center;background:var(--red,#e74c3c);border-color:var(--red,#e74c3c);">
              ↑ Record Stock Out
            </button>
            <a href="stock_out.php" class="btn btn-outline" style="flex:1;justify-content:center;">Reset</a>
          </div>

        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">How it works</div></div>
      <div class="card-body" style="font-size:13px;line-height:1.7;">
        <p>Use this to record items dispensed to patients, used in treatment, or removed from inventory.</p>
        <ul style="padding-left:18px;margin-top:8px;">
          <li>Select the item.</li>
          <li>Enter quantity being issued.</li>
          <li>Add a remark describing the purpose.</li>
          <li>Click <strong>Record Stock Out</strong>.</li>
        </ul>
        <div class="alert-msg alert-warning" style="margin-top:12px;">
          ⚠ You cannot issue more than the available stock.
        </div>
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
  const sel   = document.getElementById('itemSelect');
  const box   = document.getElementById('stockBox');
  const val   = document.getElementById('stockVal');
  const warn  = document.getElementById('lowWarn');
  const qty   = document.getElementById('qtyInput');
  const err   = document.getElementById('stockErr');

  function update(){
    const id = sel.value;
    if(id && stockMap[id]){
      const item = stockMap[id];
      val.textContent = item.qty + ' ' + item.unit;
      box.style.display = 'block';
      box.style.background = item.qty===0 ? '#fee2e2' : item.qty<=item.low ? '#fffbeb' : 'var(--bg-secondary,#f8f9fa)';
      warn.style.display = (item.qty>0 && item.qty<=item.low) ? 'inline' : 'none';
    } else { box.style.display='none'; }
    validate();
  }
  function validate(){
    const id=sel.value; const q=parseInt(qty.value)||0;
    err.style.display=(id && stockMap[id] && q>stockMap[id].qty)?'block':'none';
  }
  sel.addEventListener('change',update);
  qty.addEventListener('input',validate);
  update();
})();
</script>

<script type="module" src="../js/rolldown-runtime-_5RX-BWT.js"></script>
<script type="module" src="../js/toast-CBtjS_PZ.js"></script>
<script type="module" src="../js/main-v4-5cBbCq9P.js"></script>
</body>
</html>
