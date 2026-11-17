<?php
// ============================================================
// USER MANAGEMENT  (page1.php-style controller, Admin only)
// File: production/users.php
// ============================================================
$pageTitle    = 'User Management';
$activePage   = 'users';
$breadcrumb   = 'Home > Administration > Users';
$requireAdmin = true;
require_once('_head.php');
require_once('db.php');

if     (isset($_POST['btnAdd']))         { entryForm(); }
elseif (isset($_GET['actionEdit']))       { editForm(); }
elseif (isset($_GET['actionDelete']))     { deleteRecord(); }
elseif (isset($_GET['actionToggle']))     { toggleStatus(); }
elseif (isset($_POST['btnSave']))         { saveRecord(); }
elseif (isset($_POST['btnUpdate']))       { updateRecord(); }
else                                      { viewTable(); }

function viewTable()
{
    global $conn;
    $flash = '';
    if (isset($_GET['success'])) {
        $msgs = ['added'=>'User created.','updated'=>'User updated.','deleted'=>'User deleted.','toggled'=>'Status updated.'];
        if (isset($msgs[$_GET['success']])) $flash = '<div class="alert-msg alert-success">✓ '.$msgs[$_GET['success']].'</div>';
    }
    if (isset($_GET['error'])) {
        $errs = ['cannot_delete_self'=>'You cannot delete your own account.'];
        if (isset($errs[$_GET['error']])) $flash = '<div class="alert-msg alert-error">✗ '.$errs[$_GET['error']].'</div>';
    }

    $result = mysqli_query($conn,"SELECT * FROM users ORDER BY role ASC, full_name ASC");
    $tblRows = '';
    $ctr = 1;

    while ($row = mysqli_fetch_assoc($result)) {
        $id      = $row['id'];
        $name    = htmlspecialchars($row['full_name']);
        $uname   = htmlspecialchars($row['username']);
        $role    = ucfirst($row['role']);
        $status  = $row['status'];
        $created = date('M d, Y', strtotime($row['created_at']));

        $sBadge = $status === 'active' ? '<span class="badge-ok">Active</span>' : '<span class="badge-out">Inactive</span>';
        $rBadge = $role === 'Admin'    ? '<span class="badge-medicine">Admin</span>' : '<span class="badge-supply">Staff</span>';
        $tLabel = $status === 'active' ? 'Deactivate' : 'Activate';
        $tStyle = $status === 'active'
            ? 'background:#fef3c7;color:#92400e;border:1px solid #f59e0b;'
            : 'background:#dcfce7;color:#15803d;border:1px solid #22c55e;';

        $tblRows .= <<<HTML
        <tr>
          <td>$ctr</td><td>$name</td><td>$uname</td><td>$rBadge</td><td>$sBadge</td><td>$created</td>
          <td>
            <div class="action-btns">
              <a href="users.php?actionEdit=$id" class="btn btn-outline" style="padding:3px 10px;font-size:12px;">Edit</a>
              <a href="users.php?actionToggle=$id" onclick="return confirm('$tLabel this account?')"
                 class="btn" style="padding:3px 10px;font-size:12px;$tStyle">$tLabel</a>
              <a href="users.php?actionDelete=$id" onclick="return confirm('Delete account: $name?')"
                 class="btn" style="padding:3px 10px;font-size:12px;background:#fee2e2;color:#991b1b;border:1px solid #ef4444;">Delete</a>
            </div>
          </td>
        </tr>
HTML;
        $ctr++;
    }

    require_once('_sidebar.php');
    echo <<<HTML
    <main id="main-content" tabindex="-1" class="main">
    <div class="page-wrapper">
      <div class="page-header">
        <div class="page-header-row">
          <div><div class="page-pretitle">Administration</div><h1 class="page-title">User Accounts</h1></div>
          <div class="page-actions">
            <form method="POST" action="users.php" style="display:inline;">
              <button type="submit" name="btnAdd" class="btn btn-primary">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 8h8M8 4v8"/></svg> Add User
              </button>
            </form>
          </div>
        </div>
      </div>
      $flash
      <div class="card"><div class="card-body">
        <table class="table" style="font-size:13px;">
          <thead><tr><th>#</th><th>Full Name</th><th>Username</th><th>Role</th><th>Status</th><th>Created</th><th style="width:230px;">Actions</th></tr></thead>
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
        <div><div class="page-pretitle">Administration</div><h1 class="page-title">Add User</h1></div>
        <div class="page-actions"><a href="users.php" class="btn btn-outline">← Back</a></div>
      </div></div>
      $errHtml
      <div class="card" style="max-width:560px;">
        <div class="card-header"><div class="card-title">New User Account</div></div>
        <div class="card-body">
          <form method="POST" action="users.php">
            <div class="form-group">
              <label class="form-label">Full Name <span style="color:red">*</span></label>
              <input type="text" name="txtFullName" class="form-control" required placeholder="e.g. Maria Santos">
            </div>
            <div class="form-group">
              <label class="form-label">Username <span style="color:red">*</span></label>
              <input type="text" name="txtUsername" class="form-control" required maxlength="50" placeholder="e.g. marias">
            </div>
            <div class="row col-2" style="gap:12px;">
              <div class="form-group">
                <label class="form-label">Password <span style="color:red">*</span></label>
                <input type="password" name="txtPassword" class="form-control" required placeholder="Min. 6 characters">
              </div>
              <div class="form-group">
                <label class="form-label">Confirm Password <span style="color:red">*</span></label>
                <input type="password" name="txtConfirm" class="form-control" required placeholder="Re-enter">
              </div>
            </div>
            <div class="row col-2" style="gap:12px;">
              <div class="form-group">
                <label class="form-label">Role <span style="color:red">*</span></label>
                <select name="cmbRole" class="form-control" required>
                  <option value="">— Select —</option>
                  <option value="admin">Admin</option>
                  <option value="staff">Staff</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="cmbStatus" class="form-control">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:8px;">
              <button type="submit" name="btnSave" class="btn btn-primary">Create Account</button>
              <a href="users.php" class="btn btn-outline">Cancel</a>
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
    $stmt = mysqli_prepare($conn,"SELECT * FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt,'i',$id);
    mysqli_stmt_execute($stmt);
    $row  = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) { header('Location: users.php'); exit; }

    $name   = htmlspecialchars($row['full_name']);
    $uname  = htmlspecialchars($row['username']);
    $selAdm = $row['role']==='admin' ? 'selected' : '';
    $selStf = $row['role']==='staff' ? 'selected' : '';
    $selAct = $row['status']==='active'   ? 'selected' : '';
    $selInc = $row['status']==='inactive' ? 'selected' : '';

    require_once('_sidebar.php');
    echo <<<HTML
    <main id="main-content" tabindex="-1" class="main">
    <div class="page-wrapper">
      <div class="page-header"><div class="page-header-row">
        <div><div class="page-pretitle">Administration</div><h1 class="page-title">Edit User</h1></div>
        <div class="page-actions"><a href="users.php" class="btn btn-outline">← Back</a></div>
      </div></div>
      <div class="card" style="max-width:560px;">
        <div class="card-header"><div class="card-title">Edit: $name</div></div>
        <div class="card-body">
          <form method="POST" action="users.php">
            <input type="hidden" name="txtUserId" value="$id">
            <div class="form-group">
              <label class="form-label">Full Name <span style="color:red">*</span></label>
              <input type="text" name="txtFullName" class="form-control" value="$name" required>
            </div>
            <div class="form-group">
              <label class="form-label">Username <span style="color:red">*</span></label>
              <input type="text" name="txtUsername" class="form-control" value="$uname" required maxlength="50">
            </div>
            <div class="row col-2" style="gap:12px;">
              <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="txtPassword" class="form-control" placeholder="Leave blank to keep current">
              </div>
              <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="txtConfirm" class="form-control" placeholder="Re-enter new password">
              </div>
            </div>
            <div class="row col-2" style="gap:12px;">
              <div class="form-group">
                <label class="form-label">Role</label>
                <select name="cmbRole" class="form-control">
                  <option value="admin" $selAdm>Admin</option>
                  <option value="staff" $selStf>Staff</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="cmbStatus" class="form-control">
                  <option value="active"   $selAct>Active</option>
                  <option value="inactive" $selInc>Inactive</option>
                </select>
              </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:8px;">
              <button type="submit" name="btnUpdate" class="btn btn-primary">Update Account</button>
              <a href="users.php" class="btn btn-outline">Cancel</a>
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
    $fullName = trim($_POST['txtFullName'] ?? '');
    $username = trim($_POST['txtUsername'] ?? '');
    $password = $_POST['txtPassword']       ?? '';
    $confirm  = $_POST['txtConfirm']        ?? '';
    $role     = trim($_POST['cmbRole']      ?? '');
    $status   = trim($_POST['cmbStatus']    ?? 'active');

    if ($fullName==='' || $username==='') { entryForm('Full name and username are required.'); return; }
    if (strlen($password)<6) { entryForm('Password must be at least 6 characters.'); return; }
    if ($password !== $confirm) { entryForm('Passwords do not match.'); return; }
    if (!in_array($role,['admin','staff'])) { entryForm('Please select a valid role.'); return; }

    $stmt = mysqli_prepare($conn,"SELECT id FROM users WHERE username=?");
    mysqli_stmt_bind_param($stmt,'s',$username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) { mysqli_stmt_close($stmt); entryForm('Username already exists.'); return; }
    mysqli_stmt_close($stmt);

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = mysqli_prepare($conn,"INSERT INTO users (full_name,username,password,role,status) VALUES (?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt,'sssss',$fullName,$username,$hashed,$role,$status);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: users.php?success=added'); exit;
}

function updateRecord()
{
    global $conn;
    $id       = (int)($_POST['txtUserId']   ?? 0);
    $fullName = trim($_POST['txtFullName']  ?? '');
    $username = trim($_POST['txtUsername']  ?? '');
    $password = $_POST['txtPassword']        ?? '';
    $confirm  = $_POST['txtConfirm']         ?? '';
    $role     = trim($_POST['cmbRole']       ?? 'staff');
    $status   = trim($_POST['cmbStatus']     ?? 'active');

    if ($fullName==='' || $username==='') { header("Location: users.php?actionEdit=$id"); exit; }
    if ($password !== '' && $password !== $confirm) { header("Location: users.php?actionEdit=$id&error=password_mismatch"); exit; }

    if ($password !== '') {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn,"UPDATE users SET full_name=?,username=?,password=?,role=?,status=? WHERE id=?");
        mysqli_stmt_bind_param($stmt,'sssssi',$fullName,$username,$hashed,$role,$status,$id);
    } else {
        $stmt = mysqli_prepare($conn,"UPDATE users SET full_name=?,username=?,role=?,status=? WHERE id=?");
        mysqli_stmt_bind_param($stmt,'ssssi',$fullName,$username,$role,$status,$id);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: users.php?success=updated'); exit;
}

function toggleStatus()
{
    global $conn;
    $id = (int)($_GET['actionToggle'] ?? 0);
    $stmt = mysqli_prepare($conn,"SELECT status FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt,'i',$id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) { header('Location: users.php'); exit; }
    $newStatus = $row['status']==='active' ? 'inactive' : 'active';
    $stmt = mysqli_prepare($conn,"UPDATE users SET status=? WHERE id=?");
    mysqli_stmt_bind_param($stmt,'si',$newStatus,$id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: users.php?success=toggled'); exit;
}

function deleteRecord()
{
    global $conn;
    $id = (int)($_GET['actionDelete'] ?? 0);
    if ($id === (int)$_SESSION['user_id']) { header('Location: users.php?error=cannot_delete_self'); exit; }
    $stmt = mysqli_prepare($conn,"DELETE FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt,'i',$id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: users.php?success=deleted'); exit;
}

function renderFooter() {
    echo '</div></main><script type="module" src="../js/rolldown-runtime-_5RX-BWT.js"></script><script type="module" src="../js/toast-CBtjS_PZ.js"></script><script type="module" src="../js/main-v4-5cBbCq9P.js"></script></body></html>';
    exit;
}
?>
