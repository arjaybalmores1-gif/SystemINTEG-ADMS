<?php
// ============================================================
// CATEGORIES  (page1.php-style controller)
// File: production/categories.php
// ============================================================
$pageTitle  = 'Categories';
$activePage = 'categories';
$breadcrumb = 'Home > Inventory > Categories';
require_once('_head.php');
require_once('db.php');

if     (isset($_POST['btnAdd']))         { entryForm(); }
elseif (isset($_GET['actionEdit']))       { editForm(); }
elseif (isset($_GET['actionDelete']))     { deleteRecord(); }
elseif (isset($_POST['btnSave']))         { saveRecord(); }
elseif (isset($_POST['btnUpdate']))       { updateRecord(); }
else                                      { viewTable(); }

function viewTable()
{
    global $conn;
    $flash = '';
    if (isset($_GET['success'])) {
        $msgs = ['added'=>'Category added.','updated'=>'Category updated.','deleted'=>'Category deleted.'];
        if (isset($msgs[$_GET['success']])) $flash = '<div class="alert-msg alert-success">✓ '.$msgs[$_GET['success']].'</div>';
    }
    if (isset($_GET['error']) && $_GET['error'] === 'has_items')
        $flash = '<div class="alert-msg alert-error">✗ Cannot delete: category has inventory items.</div>';

    $result  = mysqli_query($conn, "SELECT c.*,COUNT(i.id) AS item_count FROM categories c LEFT JOIN inventory i ON c.id=i.category_id GROUP BY c.id ORDER BY c.category_name ASC");
    $numRows = mysqli_num_rows($result);
    $tblRows = '';
    $ctr = 1;

    while ($row = mysqli_fetch_assoc($result)) {
        $id   = $row['id'];
        $name = htmlspecialchars($row['category_name']);
        $desc = htmlspecialchars($row['description'] ?? '—');
        $cnt  = $row['item_count'];
        $date = date('M d, Y', strtotime($row['created_at']));
        $tblRows .= <<<HTML
        <tr>
          <td>$ctr</td><td>$name</td><td>$desc</td>
          <td><span class="badge-ok">$cnt items</span></td><td>$date</td>
          <td>
            <div class="action-btns">
              <a href="categories.php?actionEdit=$id" class="btn btn-outline" style="padding:3px 10px;font-size:12px;">Edit</a>
              <a href="categories.php?actionDelete=$id" onclick="return confirm('Delete category: $name?')"
                 class="btn" style="padding:3px 10px;font-size:12px;background:#fee2e2;color:#991b1b;border:1px solid #ef4444;">Delete</a>
            </div>
          </td>
        </tr>
HTML;
        $ctr++;
    }
    if ($numRows === 0)
        $tblRows = '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:20px;">No categories yet.</td></tr>';

    require_once('_sidebar.php');
    echo <<<HTML
    <main id="main-content" tabindex="-1" class="main">
    <div class="page-wrapper">
      <div class="page-header">
        <div class="page-header-row">
          <div><div class="page-pretitle">Inventory</div><h1 class="page-title">Categories</h1></div>
          <div class="page-actions">
            <form method="POST" action="categories.php" style="display:inline;">
              <button type="submit" name="btnAdd" class="btn btn-primary">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 8h8M8 4v8"/></svg> Add Category
              </button>
            </form>
          </div>
        </div>
      </div>
      $flash
      <div class="card"><div class="card-body">
        <table class="table" style="font-size:13px;">
          <thead><tr><th>#</th><th>Category Name</th><th>Description</th><th>Items</th><th>Created</th><th>Actions</th></tr></thead>
          <tbody>$tblRows</tbody>
        </table>
      </div></div>
HTML;
    renderFooter();
}

function entryForm($err = '')
{
    $errHtml = $err ? '<div class="alert-msg alert-error">✗ '.htmlspecialchars($err).'</div>' : '';
    require_once('_sidebar.php');
    echo <<<HTML
    <main id="main-content" tabindex="-1" class="main">
    <div class="page-wrapper">
      <div class="page-header"><div class="page-header-row">
        <div><div class="page-pretitle">Inventory</div><h1 class="page-title">Add Category</h1></div>
        <div class="page-actions"><a href="categories.php" class="btn btn-outline">← Back</a></div>
      </div></div>
      $errHtml
      <div class="card" style="max-width:520px;">
        <div class="card-header"><div class="card-title">New Category</div></div>
        <div class="card-body">
          <form method="POST" action="categories.php">
            <div class="form-group">
              <label class="form-label">Category Name <span style="color:red">*</span></label>
              <input type="text" name="txtCategoryName" class="form-control" required maxlength="100">
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea name="txtDescription" class="form-control" rows="3"></textarea>
            </div>
            <div style="display:flex;gap:8px;margin-top:8px;">
              <button type="submit" name="btnSave" class="btn btn-primary">Save</button>
              <a href="categories.php" class="btn btn-outline">Cancel</a>
            </div>
          </form>
        </div>
      </div>
HTML;
    renderFooter();
}

function editForm()
{
    global $conn;
    $id   = (int)$_GET['actionEdit'];
    $stmt = mysqli_prepare($conn,"SELECT * FROM categories WHERE id=?");
    mysqli_stmt_bind_param($stmt,'i',$id);
    mysqli_stmt_execute($stmt);
    $row  = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) { header('Location: categories.php'); exit; }

    $name = htmlspecialchars($row['category_name']);
    $desc = htmlspecialchars($row['description'] ?? '');

    require_once('_sidebar.php');
    echo <<<HTML
    <main id="main-content" tabindex="-1" class="main">
    <div class="page-wrapper">
      <div class="page-header"><div class="page-header-row">
        <div><div class="page-pretitle">Inventory</div><h1 class="page-title">Edit Category</h1></div>
        <div class="page-actions"><a href="categories.php" class="btn btn-outline">← Back</a></div>
      </div></div>
      <div class="card" style="max-width:520px;">
        <div class="card-header"><div class="card-title">Edit: $name</div></div>
        <div class="card-body">
          <form method="POST" action="categories.php">
            <input type="hidden" name="txtCategoryId" value="$id">
            <div class="form-group">
              <label class="form-label">Category Name <span style="color:red">*</span></label>
              <input type="text" name="txtCategoryName" class="form-control" value="$name" required maxlength="100">
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea name="txtDescription" class="form-control" rows="3">$desc</textarea>
            </div>
            <div style="display:flex;gap:8px;margin-top:8px;">
              <button type="submit" name="btnUpdate" class="btn btn-primary">Update</button>
              <a href="categories.php" class="btn btn-outline">Cancel</a>
            </div>
          </form>
        </div>
      </div>
HTML;
    renderFooter();
}

function saveRecord()
{
    global $conn;
    $name = trim($_POST['txtCategoryName'] ?? '');
    $desc = trim($_POST['txtDescription']  ?? '');
    if ($name === '') { entryForm('Category name is required.'); return; }

    $stmt = mysqli_prepare($conn,"SELECT id FROM categories WHERE category_name=?");
    mysqli_stmt_bind_param($stmt,'s',$name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) { mysqli_stmt_close($stmt); entryForm('Category name already exists.'); return; }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn,"INSERT INTO categories (category_name,description) VALUES (?,?)");
    mysqli_stmt_bind_param($stmt,'ss',$name,$desc);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: categories.php?success=added'); exit;
}

function updateRecord()
{
    global $conn;
    $id   = (int)($_POST['txtCategoryId'] ?? 0);
    $name = trim($_POST['txtCategoryName'] ?? '');
    $desc = trim($_POST['txtDescription']  ?? '');
    if ($name === '') { header("Location: categories.php?actionEdit=$id"); exit; }

    $stmt = mysqli_prepare($conn,"UPDATE categories SET category_name=?,description=? WHERE id=?");
    mysqli_stmt_bind_param($stmt,'ssi',$name,$desc,$id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: categories.php?success=updated'); exit;
}

function deleteRecord()
{
    global $conn;
    $id = (int)($_GET['actionDelete'] ?? 0);
    $stmt = mysqli_prepare($conn,"SELECT COUNT(*) AS c FROM inventory WHERE category_id=?");
    mysqli_stmt_bind_param($stmt,'i',$id);
    mysqli_stmt_execute($stmt);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'] > 0) {
        header('Location: categories.php?error=has_items'); exit;
    }
    mysqli_stmt_close($stmt);
    $stmt = mysqli_prepare($conn,"DELETE FROM categories WHERE id=?");
    mysqli_stmt_bind_param($stmt,'i',$id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: categories.php?success=deleted'); exit;
}

function renderFooter() {
    echo '</div></main><script type="module" src="../js/rolldown-runtime-_5RX-BWT.js"></script><script type="module" src="../js/toast-CBtjS_PZ.js"></script><script type="module" src="../js/main-v4-5cBbCq9P.js"></script></body></html>';
    exit;
}
?>
