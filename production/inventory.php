<?php
// ============================================================
// INVENTORY MANAGEMENT  (page1.php-style controller)
// File: production/inventory.php
// Actions: view list | add form | edit form | save | update | delete
// ============================================================
$pageTitle  = 'Inventory';
$activePage = 'inventory';
$breadcrumb = 'Home > Inventory > Items List';
require_once('_head.php');
require_once('db.php');

// ── CONTROLLER ───────────────────────────────────────────────
if     (isset($_POST['btnAdd']))          { entryForm(); }
elseif (isset($_GET['actionEdit']))        { editForm(); }
elseif (isset($_GET['actionDelete']))      { deleteRecord(); }
elseif (isset($_POST['btnSave']))          { saveRecord(); }
elseif (isset($_POST['btnUpdate']))        { updateRecord(); }
elseif (isset($_GET['action']) && $_GET['action'] === 'add') { entryForm(); }
// AJAX: live duplicate name check — returns JSON {exists: true/false}
elseif (isset($_GET['checkDup'])) {
    $name = trim($_GET['name'] ?? '');
    $stmt = mysqli_prepare($conn, "SELECT id FROM inventory WHERE item_name = ?");
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $found = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    header('Content-Type: application/json');
    echo json_encode(['exists' => $found]);
    exit;
}
else                                       { viewTable(); }

// ── VIEW TABLE ───────────────────────────────────────────────
function viewTable()
{
    global $conn;

    $search    = trim($_GET['search']   ?? '');
    $catFilter = trim($_GET['category'] ?? '');
    $typeFilter= trim($_GET['type']     ?? '');

    $where  = "WHERE 1=1";
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where   .= " AND i.item_name LIKE ?";
        $params[] = "%$search%";
        $types   .= 's';
    }
    if ($catFilter !== '') {
        $where   .= " AND i.category_id = ?";
        $params[] = (int)$catFilter;
        $types   .= 'i';
    }
    if ($typeFilter !== '') {
        $where   .= " AND i.item_type = ?";
        $params[] = $typeFilter;
        $types   .= 's';
    }

    $stmt = mysqli_prepare($conn,
        "SELECT i.*, c.category_name FROM inventory i
         JOIN categories c ON i.category_id = c.id $where ORDER BY i.item_name ASC");
    if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result  = mysqli_stmt_get_result($stmt);
    $numRows = mysqli_num_rows($result);

    $catResult = mysqli_query($conn, "SELECT id, category_name FROM categories ORDER BY category_name");

    // Flash message
    $flash = '';
    if (isset($_GET['success'])) {
        $msgs = ['added'=>'Item added.','updated'=>'Item updated.','deleted'=>'Item deleted.'];
        if (isset($msgs[$_GET['success']]))
            $flash = '<div class="alert-msg alert-success">✓ '.$msgs[$_GET['success']].'</div>';
    }
    if (isset($_GET['error']) && $_GET['error'] === 'has_transactions')
        $flash = '<div class="alert-msg alert-error">✗ Cannot delete: item has existing transaction records.</div>';
    if (isset($_GET['error']) && $_GET['error'] === 'delete_failed')
        $flash = '<div class="alert-msg alert-error">✗ Delete failed. Please try again.</div>';

    // Build rows
    $tblRows = '';
    $ctr = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $id   = $row['id'];
        $name = htmlspecialchars($row['item_name']);
        $cat  = htmlspecialchars($row['category_name']);
        $type = htmlspecialchars($row['item_type']);
        $qty  = number_format($row['quantity']);
        $unit = htmlspecialchars($row['unit']);
        $cost = number_format($row['unit_cost'], 2);
        $exp  = $row['expiration_date'] ? date('M d, Y', strtotime($row['expiration_date'])) : '—';

        $statusBadge = $row['quantity'] == 0
            ? '<span class="badge-out">Out of Stock</span>'
            : ($row['quantity'] <= $row['low_stock_level']
                ? '<span class="badge-low">Low Stock</span>'
                : '<span class="badge-ok">OK</span>');

        $typeBadge = $type === 'Medicine'
            ? '<span class="badge-medicine">Medicine</span>'
            : '<span class="badge-supply">Supply</span>';

        $tblRows .= <<<HTML
        <tr>
          <td>$ctr</td>
          <td>$name</td>
          <td>$cat</td>
          <td>$typeBadge</td>
          <td>$qty $unit</td>
          <td>₱$cost</td>
          <td>$exp</td>
          <td>$statusBadge</td>
          <td>
            <div class="action-btns">
              <a href="inventory.php?actionEdit=$id" class="btn btn-outline" style="padding:3px 10px;font-size:12px;">Edit</a>
              <a href="inventory.php?actionDelete=$id"
                 onclick="return confirm('Delete: $name?\\nThis will also delete all transaction records for this item.\\nThis cannot be undone.')"
                 class="btn" style="padding:3px 10px;font-size:12px;background:#fee2e2;color:#991b1b;border:1px solid #ef4444;">Delete</a>
            </div>
          </td>
        </tr>
HTML;
        $ctr++;
    }
    if ($numRows === 0)
        $tblRows = '<tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:20px;">No items found.</td></tr>';

    // Category options
    $catOpts = '<option value="">All Categories</option>';
    while ($c = mysqli_fetch_assoc($catResult)) {
        $sel = ($catFilter == $c['id']) ? 'selected' : '';
        $catOpts .= "<option value=\"{$c['id']}\" $sel>".htmlspecialchars($c['category_name'])."</option>";
    }
    $selMed = $typeFilter === 'Medicine'       ? 'selected' : '';
    $selSup = $typeFilter === 'Medical Supply' ? 'selected' : '';
    $sv = htmlspecialchars($search);

    require_once('_sidebar.php');
    echo <<<HTML
    <main id="main-content" tabindex="-1" class="main">
    <div class="page-wrapper">
      <div class="page-header">
        <div class="page-header-row">
          <div><div class="page-pretitle">Inventory Management</div><h1 class="page-title">Inventory Items</h1></div>
          <div class="page-actions">
            <form method="POST" action="inventory.php" style="display:inline;">
              <button type="submit" name="btnAdd" class="btn btn-primary">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 8h8M8 4v8"/></svg>
                Add New Item
              </button>
            </form>
          </div>
        </div>
      </div>
      $flash
      <div class="card">
        <div class="card-body">
          <form method="GET" action="inventory.php">
            <div class="search-filter-row">
              <input type="text" name="search" class="form-control" placeholder="Search item name..." value="$sv">
              <select name="category" class="form-control">$catOpts</select>
              <select name="type" class="form-control">
                <option value="">All Types</option>
                <option value="Medicine" $selMed>Medicine</option>
                <option value="Medical Supply" $selSup>Medical Supply</option>
              </select>
              <button type="submit" class="btn btn-primary" style="height:38px;">Search</button>
              <a href="inventory.php" class="btn btn-outline" style="height:38px;">Reset</a>
            </div>
          </form>
          <table class="table" style="font-size:13px;">
            <thead>
              <tr><th>#</th><th>Item Name</th><th>Category</th><th>Type</th><th>Quantity</th><th>Unit Cost</th><th>Expiry</th><th>Status</th><th style="width:170px;">Actions</th></tr>
            </thead>
            <tbody>$tblRows</tbody>
          </table>
          <div style="font-size:12.5px;color:var(--text-muted);margin-top:8px;">Showing $numRows record(s)</div>
        </div>
      </div>
HTML;
    renderFooter();
}

// ── ENTRY FORM (ADD) ─────────────────────────────────────────
function entryForm($errorMsg = '')
{
    global $conn;
    $catResult = mysqli_query($conn, "SELECT id, category_name FROM categories ORDER BY category_name");

    $catOpts = '<option value="">— Select Category —</option>';
    while ($c = mysqli_fetch_assoc($catResult))
        $catOpts .= "<option value=\"{$c['id']}\">".htmlspecialchars($c['category_name'])."</option>";

    $err = $errorMsg ? '<div class="alert-msg alert-error">✗ '.htmlspecialchars($errorMsg).'</div>' : '';

    require_once('_sidebar.php');
    global $breadcrumb;
    $breadcrumb = 'Home > Inventory > Add Item';
    echo <<<HTML
    <main id="main-content" tabindex="-1" class="main">
    <div class="page-wrapper">
      <div class="page-header">
        <div class="page-header-row">
          <div><div class="page-pretitle">Inventory</div><h1 class="page-title">Add New Item</h1></div>
          <div class="page-actions"><a href="inventory.php" class="btn btn-outline">← Back to List</a></div>
        </div>
      </div>
      $err
      <form method="POST" action="inventory.php">
        <div class="row col-8-4" style="align-items:flex-start;">
          <div class="card">
            <div class="card-header"><div class="card-title">Item Information</div></div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Item Name <span style="color:red">*</span></label>
                <input type="text" name="txtItemName" id="txtItemName" class="form-control"
                       required maxlength="150" placeholder="e.g. Paracetamol 500mg"
                       autocomplete="off">
                <!-- Duplicate warning shown by JS while typing -->
                <div id="dupWarn" style="display:none;margin-top:5px;padding:7px 12px;background:#fffbeb;border-left:3px solid #f59e0b;border-radius:5px;font-size:12.5px;color:#92400e;">
                  ⚠ An item with this name already exists in the inventory.
                </div>
              </div>
              <div class="row col-2" style="gap:12px;">
                <div class="form-group">
                  <label class="form-label">Category <span style="color:red">*</span></label>
                  <select name="cmbCategory" class="form-control" required><option value="">— Select —</option>$catOpts</select>
                </div>
                <div class="form-group">
                  <label class="form-label">Item Type <span style="color:red">*</span></label>
                  <select name="cmbItemType" class="form-control" required>
                    <option value="">— Select —</option>
                    <option value="Medicine">Medicine</option>
                    <option value="Medical Supply">Medical Supply</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="txtDescription" class="form-control" rows="3" placeholder="Brief description..."></textarea>
              </div>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card">
              <div class="card-header"><div class="card-title">Stock & Pricing</div></div>
              <div class="card-body">
                <div class="form-group">
                  <label class="form-label">Initial Quantity <span style="color:red">*</span></label>
                  <input type="number" name="txtQuantity" class="form-control" min="0" value="0" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Unit of Measurement <span style="color:red">*</span></label>
                  <input type="text" name="txtUnit" class="form-control" required maxlength="30" placeholder="e.g. Tablet, Box, Piece">
                </div>
                <div class="form-group">
                  <label class="form-label">Low Stock Alert Level</label>
                  <input type="number" name="txtLowStock" class="form-control" min="0" value="10">
                </div>
                <div class="form-group">
                  <label class="form-label">Expiration Date</label>
                  <input type="date" name="txtExpiration" class="form-control">
                  <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">Leave blank if no expiry.</div>
                </div>
                <div class="form-group">
                  <label class="form-label">Unit Cost (₱)</label>
                  <input type="number" name="txtUnitCost" class="form-control" min="0" step="0.01" value="0.00">
                </div>
                <div class="form-group">
                  <label class="form-label">Issuance Price (₱)</label>
                  <input type="number" name="txtIssuancePrice" class="form-control" min="0" step="0.01" value="0.00">
                </div>
              </div>
            </div>
            <div style="display:flex;gap:8px;">
              <button type="submit" name="btnSave" id="btnSave" class="btn btn-primary" style="flex:1;justify-content:center;">Save Item</button>
              <a href="inventory.php" class="btn btn-outline" style="flex:1;justify-content:center;">Cancel</a>
            </div>
          </div>
        </div>
      </form>

      <script>
      // ── LIVE DUPLICATE NAME CHECK ───────────────────────────
      // As the user types in the Item Name field, we check
      // the database via fetch() (AJAX) to see if the name
      // already exists. We show/hide a warning and
      // block the Save button if a duplicate is found.
      (function () {
        const input   = document.getElementById('txtItemName');
        const warn    = document.getElementById('dupWarn');
        const saveBtn = document.getElementById('btnSave');
        let   timer   = null;

        input.addEventListener('input', function () {
          clearTimeout(timer);
          const val = this.value.trim();

          if (val.length < 2) {
            warn.style.display = 'none';
            saveBtn.disabled = false;
            return;
          }

          // Wait 400ms after the user stops typing before checking
          timer = setTimeout(function () {
            fetch('inventory.php?checkDup=1&name=' + encodeURIComponent(val))
              .then(r => r.json())
              .then(data => {
                if (data.exists) {
                  warn.style.display = 'block';
                  saveBtn.disabled   = true;
                } else {
                  warn.style.display = 'none';
                  saveBtn.disabled   = false;
                }
              });
          }, 400);
        });
      })();
      </script>
HTML;
    renderFooter();
}

// ── EDIT FORM ────────────────────────────────────────────────
function editForm()
{
    global $conn;
    $id   = (int)$_GET['actionEdit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM inventory WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$item) { header('Location: inventory.php'); exit; }

    $catResult = mysqli_query($conn, "SELECT id, category_name FROM categories ORDER BY category_name");
    $catOpts = '';
    while ($c = mysqli_fetch_assoc($catResult)) {
        $sel = ($item['category_id'] == $c['id']) ? 'selected' : '';
        $catOpts .= "<option value=\"{$c['id']}\" $sel>".htmlspecialchars($c['category_name'])."</option>";
    }

    $selMed = $item['item_type'] === 'Medicine'       ? 'selected' : '';
    $selSup = $item['item_type'] === 'Medical Supply' ? 'selected' : '';
    $name   = htmlspecialchars($item['item_name']);
    $desc   = htmlspecialchars($item['description'] ?? '');
    $unit   = htmlspecialchars($item['unit']);
    $cost   = htmlspecialchars($item['unit_cost']);
    $iPrice = htmlspecialchars($item['issuance_price'] ?? '0.00');
    $low    = htmlspecialchars($item['low_stock_level']);
    $exp    = htmlspecialchars($item['expiration_date'] ?? '');
    $qty    = number_format($item['quantity']);

    require_once('_sidebar.php');
    echo <<<HTML
    <main id="main-content" tabindex="-1" class="main">
    <div class="page-wrapper">
      <div class="page-header">
        <div class="page-header-row">
          <div><div class="page-pretitle">Inventory</div><h1 class="page-title">Edit Item</h1></div>
          <div class="page-actions"><a href="inventory.php" class="btn btn-outline">← Back to List</a></div>
        </div>
      </div>
      <form method="POST" action="inventory.php">
        <input type="hidden" name="txtItemId" value="$id">
        <div class="row col-8-4" style="align-items:flex-start;">
          <div class="card">
            <div class="card-header"><div class="card-title">Item Information</div></div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Item Name <span style="color:red">*</span></label>
                <input type="text" name="txtItemName" class="form-control" value="$name" required maxlength="150">
              </div>
              <div class="row col-2" style="gap:12px;">
                <div class="form-group">
                  <label class="form-label">Category <span style="color:red">*</span></label>
                  <select name="cmbCategory" class="form-control" required><option value="">— Select —</option>$catOpts</select>
                </div>
                <div class="form-group">
                  <label class="form-label">Item Type <span style="color:red">*</span></label>
                  <select name="cmbItemType" class="form-control" required>
                    <option value="">— Select —</option>
                    <option value="Medicine" $selMed>Medicine</option>
                    <option value="Medical Supply" $selSup>Medical Supply</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="txtDescription" class="form-control" rows="3">$desc</textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Current Stock (read-only)</label>
                <div style="padding:8px 12px;background:var(--bg-secondary,#f8f9fa);border-radius:6px;font-size:13.5px;font-weight:500;">
                  $qty $unit &nbsp;<span style="font-size:12px;color:var(--text-muted);font-weight:400;">(Use Stock In / Stock Out to change)</span>
                </div>
              </div>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card">
              <div class="card-header"><div class="card-title">Stock & Pricing</div></div>
              <div class="card-body">
                <div class="form-group">
                  <label class="form-label">Unit of Measurement <span style="color:red">*</span></label>
                  <input type="text" name="txtUnit" class="form-control" value="$unit" required maxlength="30">
                </div>
                <div class="form-group">
                  <label class="form-label">Low Stock Alert Level</label>
                  <input type="number" name="txtLowStock" class="form-control" min="0" value="$low">
                </div>
                <div class="form-group">
                  <label class="form-label">Expiration Date</label>
                  <input type="date" name="txtExpiration" class="form-control" value="$exp">
                </div>
                <div class="form-group">
                  <label class="form-label">Unit Cost (₱)</label>
                  <input type="number" name="txtUnitCost" class="form-control" min="0" step="0.01" value="$cost">
                </div>
                <div class="form-group">
                  <label class="form-label">Issuance Price (₱)</label>
                  <input type="number" name="txtIssuancePrice" class="form-control" min="0" step="0.01" value="$iPrice">
                </div>
              </div>
            </div>
            <div style="display:flex;gap:8px;">
              <button type="submit" name="btnUpdate" class="btn btn-primary" style="flex:1;justify-content:center;">Update Item</button>
              <a href="inventory.php" class="btn btn-outline" style="flex:1;justify-content:center;">Cancel</a>
            </div>
          </div>
        </div>
      </form>
HTML;
    renderFooter();
}

// ── SAVE ─────────────────────────────────────────────────────
function saveRecord()
{
    global $conn;
    $itemName      = trim($_POST['txtItemName']      ?? '');
    $categoryId    = (int)($_POST['cmbCategory']     ?? 0);
    $itemType      = trim($_POST['cmbItemType']      ?? '');
    $description   = trim($_POST['txtDescription']   ?? '');
    $quantity      = (int)($_POST['txtQuantity']     ?? 0);
    $unit          = trim($_POST['txtUnit']          ?? '');
    $unitCost      = (float)($_POST['txtUnitCost']   ?? 0);
    $issuancePrice = (float)($_POST['txtIssuancePrice'] ?? 0);
    $expirationDate= trim($_POST['txtExpiration']    ?? '');
    $lowStockLevel = (int)($_POST['txtLowStock']     ?? 10);

    if ($itemName === '' || $categoryId <= 0 || !in_array($itemType, ['Medicine','Medical Supply']) || $unit === '') {
        entryForm('Please fill in all required fields correctly.');
        return;
    }

    // Duplicate check
    $stmt = mysqli_prepare($conn, "SELECT id FROM inventory WHERE item_name = ?");
    mysqli_stmt_bind_param($stmt, 's', $itemName);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        entryForm('An item with this name already exists.');
        return;
    }
    mysqli_stmt_close($stmt);

    $expVal = $expirationDate !== '' ? $expirationDate : null;

    $stmt = mysqli_prepare($conn,
        "INSERT INTO inventory (item_name,category_id,item_type,description,quantity,unit,unit_cost,issuance_price,expiration_date,low_stock_level)
         VALUES (?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'sissisddsi',
        $itemName,$categoryId,$itemType,$description,$quantity,$unit,$unitCost,$issuancePrice,$expVal,$lowStockLevel);
    mysqli_stmt_execute($stmt);
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if ($quantity > 0) {
        $userId  = $_SESSION['user_id'];
        $remarks = 'Initial stock on item creation.';
        $stmt2 = mysqli_prepare($conn,
            "INSERT INTO inventory_transactions (inventory_id,user_id,transaction_type,quantity,remarks) VALUES (?,?,'Stock In',?,?)");
        mysqli_stmt_bind_param($stmt2,'iiis',$newId,$userId,$quantity,$remarks);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    }

    header('Location: inventory.php?success=added');
    exit;
}

// ── UPDATE ───────────────────────────────────────────────────
function updateRecord()
{
    global $conn;
    $id            = (int)($_POST['txtItemId']        ?? 0);
    $itemName      = trim($_POST['txtItemName']       ?? '');
    $categoryId    = (int)($_POST['cmbCategory']      ?? 0);
    $itemType      = trim($_POST['cmbItemType']       ?? '');
    $description   = trim($_POST['txtDescription']    ?? '');
    $unit          = trim($_POST['txtUnit']           ?? '');
    $unitCost      = (float)($_POST['txtUnitCost']    ?? 0);
    $issuancePrice = (float)($_POST['txtIssuancePrice'] ?? 0);
    $expirationDate= trim($_POST['txtExpiration']     ?? '');
    $lowStockLevel = (int)($_POST['txtLowStock']      ?? 10);

    if ($itemName === '' || $categoryId <= 0 || $unit === '') {
        header("Location: inventory.php?actionEdit=$id");
        exit;
    }

    $expVal = $expirationDate !== '' ? $expirationDate : null;

    $stmt = mysqli_prepare($conn,
        "UPDATE inventory SET item_name=?,category_id=?,item_type=?,description=?,unit=?,unit_cost=?,issuance_price=?,expiration_date=?,low_stock_level=? WHERE id=?");
    mysqli_stmt_bind_param($stmt,'sisssddsii',
        $itemName,$categoryId,$itemType,$description,$unit,$unitCost,$issuancePrice,$expVal,$lowStockLevel,$id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: inventory.php?success=updated');
    exit;
}

// ── DELETE ───────────────────────────────────────────────────
function deleteRecord()
{
    global $conn;
    $id = (int)($_GET['actionDelete'] ?? 0);

    if ($id <= 0) { header('Location: inventory.php'); exit; }

    // Use a transaction: delete related transaction records first,
    // then delete the inventory item — both succeed or both roll back.
    mysqli_begin_transaction($conn);

    try {
        // 1. Delete all transaction records linked to this item
        $stmt = mysqli_prepare($conn, "DELETE FROM inventory_transactions WHERE inventory_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // 2. Delete the inventory item itself
        $stmt = mysqli_prepare($conn, "DELETE FROM inventory WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        header('Location: inventory.php?success=deleted');
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        header('Location: inventory.php?error=delete_failed');
        exit;
    }
}

// ── SHARED FOOTER ────────────────────────────────────────────
function renderFooter()
{
    echo <<<HTML
    </div></main>
    <script type="module" src="../js/rolldown-runtime-_5RX-BWT.js"></script>
    <script type="module" src="../js/toast-CBtjS_PZ.js"></script>
    <script type="module" src="../js/main-v4-5cBbCq9P.js"></script>
    </body></html>
HTML;
    exit;
}
?>
