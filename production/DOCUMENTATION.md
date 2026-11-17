# Clinic Inventory Management System
## Complete System Documentation

---

## Table of Contents
1. [Project Overview](#1-project-overview)
2. [Technology Stack](#2-technology-stack)
3. [Project Structure](#3-project-structure)
4. [Database Design](#4-database-design)
5. [File Reference](#5-file-reference)
6. [Code Structure Pattern](#6-code-structure-pattern)
7. [System Features](#7-system-features)
8. [How Data Flows](#8-how-data-flows)
9. [Installation Guide](#9-installation-guide)
10. [Default Accounts](#10-default-accounts)
11. [Page-by-Page Guide](#11-page-by-page-guide)

---

## 1. Project Overview

The **Clinic Inventory Management System (Clinic IMS)** is a web-based system built for managing clinic medicines and medical supplies. It tracks inventory items, monitors stock levels, records all stock movements, and generates reports.

It is built on top of the **Gentelella v4 Admin Dashboard Template** — the existing HTML design is reused and converted into PHP pages. No new design framework was introduced.

---

## 2. Technology Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Frontend    | HTML5, CSS3, JavaScript (Gentelella v4) |
| Backend     | PHP (procedural, MySQLi)            |
| Database    | MySQL via XAMPP                     |
| Local Server| XAMPP (Apache + MySQL)              |
| DB Manager  | phpMyAdmin                          |
| Code Editor | Visual Studio Code                  |

---

## 3. Project Structure

All system PHP files live inside the existing `production/` folder.
The `clinic-inventory/` folder (created earlier) is kept but NOT used in this version — everything runs from `production/`.

```
production/
│
├── _head.php          ← Shared HTML <head> + session guard
├── _sidebar.php       ← Shared sidebar navigation + topbar
├── db.php             ← Database connection (MySQLi)
│
├── login.php          ← Login page
├── logout.php         ← Session destroy + redirect
├── setup.php          ← One-time password setup utility
│
├── dashboard.php      ← Main dashboard with live stats
│
├── inventory.php      ← Inventory CRUD (list, add, edit, delete)
├── categories.php     ← Category CRUD
│
├── stock_in.php       ← Record stock received
├── stock_out.php      ← Record stock issued/dispensed
├── transactions.php   ← Full transaction history with filters
│
├── reports.php        ← Reports with filters + print support
│
└── users.php          ← User account management (Admin only)
```

**Supporting assets (not modified):**
```
assets/
  main-v4-DB_ReeJG.css   ← Gentelella compiled CSS (do not modify)
js/
  rolldown-runtime-*.js  ← Gentelella JS runtime
  main-v4-*.js           ← Gentelella core JS
  toast-*.js             ← Toast notification JS
images/
  favicon.svg, logo.svg, etc.
```

---

## 4. Database Design

**Database name:** `clinic_inventory`

Import the SQL file at:
```
clinic-inventory/config/clinic_inventory.sql
```

---

### Table: `users`
Stores all system user accounts.

| Column       | Type                     | Description                        |
|--------------|--------------------------|------------------------------------|
| id           | INT AUTO_INCREMENT PK    | Unique user ID                     |
| full_name    | VARCHAR(100)             | Full name of the user              |
| username     | VARCHAR(50) UNIQUE       | Login username                     |
| password     | VARCHAR(255)             | Bcrypt hashed password             |
| role         | ENUM('admin','staff')    | Access level                       |
| status       | ENUM('active','inactive')| Account status                     |
| created_at   | DATETIME                 | Date account was created           |

---

### Table: `categories`
Organizes inventory items into groups.

| Column        | Type                  | Description              |
|---------------|-----------------------|--------------------------|
| id            | INT AUTO_INCREMENT PK | Unique category ID       |
| category_name | VARCHAR(100) UNIQUE   | Name of the category     |
| description   | TEXT                  | Optional description     |
| created_at    | DATETIME              | Date category was created|

---

### Table: `inventory`
Master list of all medicines and medical supplies.

| Column          | Type                             | Description                        |
|-----------------|----------------------------------|------------------------------------|
| id              | INT AUTO_INCREMENT PK            | Unique item ID                     |
| item_name       | VARCHAR(150)                     | Name of the medicine/supply        |
| category_id     | INT FK → categories.id           | Which category it belongs to       |
| item_type       | ENUM('Medicine','Medical Supply')| Type classification                |
| description     | TEXT                             | Description of the item            |
| quantity        | INT                              | Current available stock            |
| unit            | VARCHAR(30)                      | Unit of measurement (Tablet, Box…) |
| unit_cost       | DECIMAL(10,2)                    | Purchase cost per unit             |
| issuance_price  | DECIMAL(10,2)                    | Dispensing/selling price           |
| expiration_date | DATE (nullable)                  | Expiry date, NULL if none          |
| low_stock_level | INT                              | Alert threshold for low stock      |
| created_at      | DATETIME                         | Date item was added                |
| updated_at      | DATETIME                         | Last update timestamp (auto)       |

---

### Table: `inventory_transactions`
Records every single stock movement (in or out).

| Column           | Type                           | Description                        |
|------------------|--------------------------------|------------------------------------|
| id               | INT AUTO_INCREMENT PK          | Unique transaction ID              |
| inventory_id     | INT FK → inventory.id          | Which item was moved               |
| user_id          | INT FK → users.id              | Who performed the transaction      |
| transaction_type | ENUM('Stock In','Stock Out')   | Direction of the movement          |
| quantity         | INT                            | How many units were moved          |
| remarks          | TEXT                           | Notes / reason for the transaction |
| transaction_date | DATETIME (auto)                | Exact date and time recorded       |

---

### Relationships

```
categories  ──< inventory ──< inventory_transactions
users       ──────────────────────────────────────^
```

- One category → many inventory items
- One inventory item → many transactions
- One user → many transactions

---

## 5. File Reference

| File               | Purpose                                | Auth Required | Admin Only |
|--------------------|----------------------------------------|---------------|------------|
| `login.php`        | User login form + authentication       | No            | No         |
| `logout.php`       | Destroy session, redirect to login     | No            | No         |
| `setup.php`        | Set passwords for default accounts     | No            | No         |
| `db.php`           | MySQLi database connection             | No            | No         |
| `_head.php`        | HTML head + session guard include      | Yes           | No         |
| `_sidebar.php`     | Sidebar + topbar include               | Yes           | No         |
| `dashboard.php`    | Live stats dashboard                   | Yes           | No         |
| `inventory.php`    | Full CRUD for inventory items          | Yes           | No         |
| `categories.php`   | Full CRUD for item categories          | Yes           | No         |
| `stock_in.php`     | Record items added to stock            | Yes           | No         |
| `stock_out.php`    | Record items removed from stock        | Yes           | No         |
| `transactions.php` | View full transaction history          | Yes           | No         |
| `reports.php`      | Filtered reports + print               | Yes           | No         |
| `users.php`        | Manage user accounts                   | Yes           | **Yes**    |

---

## 6. Code Structure Pattern

All PHP pages follow the same **controller pattern** used in `page1.php` from the original project.

### Pattern Explanation

```php
// 1. Set page variables FIRST
$pageTitle  = 'Inventory';
$activePage = 'inventory';
$breadcrumb = 'Home > Inventory';

// 2. Include session guard (redirects to login if not logged in)
require_once('_head.php');

// 3. Include database connection
require_once('db.php');

// 4. CONTROLLER — decides which function to run
if     (isset($_POST['btnAdd']))      { entryForm(); }
elseif (isset($_GET['actionEdit']))   { editForm(); }
elseif (isset($_GET['actionDelete'])) { deleteRecord(); }
elseif (isset($_POST['btnSave']))     { saveRecord(); }
elseif (isset($_POST['btnUpdate']))   { updateRecord(); }
else                                  { viewTable(); }

// 5. FUNCTIONS — each handles one action
function viewTable()   { /* SELECT + display table */ }
function entryForm()   { /* Show empty add form */ }
function editForm()    { /* SELECT one record + show filled form */ }
function saveRecord()  { /* Validate + INSERT */ }
function updateRecord(){ /* Validate + UPDATE */ }
function deleteRecord(){ /* DELETE with safety check */ }
```

### How the sidebar knows which link is active

Each page sets `$activePage` before including `_sidebar.php`.
The sidebar checks this value to highlight the correct nav item:

```php
// In each page file:
$activePage = 'inventory';   // tells sidebar which item to highlight

// In _sidebar.php:
function sa($page, $active) {
    return $page === $active ? ' active" aria-current="page' : '';
}
// Used as:
<a class="nav-sublink<?php echo sa('inventory',$activePage); ?>" href="inventory.php">
```

### How includes work

```
inventory.php
  ├── require_once('_head.php')    ← outputs HTML <head>, session check
  ├── require_once('db.php')       ← creates $conn (MySQLi connection)
  └── [inside each function]:
      ├── require_once('_sidebar.php')  ← outputs <aside> + <header>
      └── [page content HTML]
          └── renderFooter()       ← outputs closing JS tags + </body></html>
```

---

## 7. System Features

### Login & Authentication
- Username/password form at `login.php`
- Passwords stored as **bcrypt hashes** using `password_hash()`
- Verified using `password_verify()`
- PHP sessions store: `user_id`, `full_name`, `role`, `username`
- Every protected page starts with `_head.php` which redirects to login if session is missing
- Admin-only pages set `$requireAdmin = true` before including `_head.php`

### Dashboard
- Shows 5 live stat cards pulled from MySQL:
  - Total inventory items
  - Total stock units
  - Low stock items count
  - Out of stock items count
  - Items expiring within 30 days
- Recent 10 transactions table
- Low stock alert list (top 5)
- Expiring items alert list (top 5)

### Inventory Management (`inventory.php`)
- **View**: Searchable, filterable table of all items
- **Add**: Form creates new item, records initial quantity as a Stock In transaction
- **Edit**: Pre-filled form updates item details (quantity only changes via transactions)
- **Delete**: Blocked if the item has existing transaction records
- **Search**: By item name
- **Filter**: By category and item type (Medicine / Medical Supply)
- Status badges: OK (green), Low Stock (yellow), Out of Stock (red)

### Categories (`categories.php`)
- Add, edit, delete categories
- Delete blocked if any inventory items use that category
- Shows item count per category

### Stock In (`stock_in.php`)
- Dropdown lists all inventory items with current stock
- JavaScript shows live current stock when item is selected
- On submit: **MySQL transaction** ensures both steps succeed together:
  1. `UPDATE inventory SET quantity = quantity + ?`
  2. `INSERT INTO inventory_transactions`
- If either step fails, both are rolled back

### Stock Out (`stock_out.php`)
- Same as Stock In but decreases stock
- **Validates** that issued quantity does not exceed available stock
- JavaScript shows live stock + warning if low stock
- Uses `AND quantity >= ?` in the UPDATE to prevent race conditions
- MySQL transaction wraps both the UPDATE and INSERT

### Transaction History (`transactions.php`)
- Full table of all transactions
- **Filter by**: item name, transaction type, date range
- Summary cards: total shown, Stock In count, Stock Out count

### Reports (`reports.php`)
- Switchable between **Inventory Report** and **Transaction Report**
- Quick filter pills: Low Stock, Out of Stock, Expiring Soon, All Inventory, Transactions
- Filter form: by category, item type, date range
- **Print button** hides sidebar/navbar and prints the table cleanly

### User Management (`users.php`) — Admin Only
- View all user accounts
- Add new user with role and password
- Edit user info and optionally change password
- Activate / Deactivate accounts (toggle)
- Delete accounts (cannot delete your own account)
- Role-based access: `admin` sees all features, `staff` cannot access `users.php`

---

## 8. How Data Flows

### Adding an Inventory Item

```
User fills form → clicks "Save Item"
  → inventory.php receives $_POST['btnSave']
  → saveRecord() is called
  → Validate: item name, category, type, unit required
  → Check duplicate: SELECT id FROM inventory WHERE item_name = ?
  → If duplicate: show entryForm() with error message
  → If ok: INSERT INTO inventory (item_name, category_id, ...)
  → If initial quantity > 0: also INSERT INTO inventory_transactions (type='Stock In')
  → header('Location: inventory.php?success=added')
  → User sees success message on the list page
```

### Recording Stock Out

```
User selects item → enters quantity → clicks "Record Stock Out"
  → stock_out.php receives $_POST['btnStockOut']
  → Validate: item selected, quantity > 0
  → SELECT quantity FROM inventory WHERE id = ?
  → If quantity entered > available: show error "Insufficient stock"
  → If ok: mysqli_begin_transaction()
      → UPDATE inventory SET quantity = quantity - ? WHERE id = ? AND quantity >= ?
      → If affected rows = 0: throw Exception (concurrent change guard)
      → INSERT INTO inventory_transactions (type='Stock Out', ...)
      → mysqli_commit()
  → If any error: mysqli_rollback()
  → Show success or error message
```

### Login Flow

```
User enters username + password → clicks "Sign In"
  → login.php receives $_POST['btnLogin']
  → SELECT id, full_name, password, role, status FROM users WHERE username = ?
  → password_verify($inputPassword, $row['password'])
  → If false: "Incorrect username or password"
  → If true + status = 'inactive': "Account deactivated"
  → If true + active:
      → $_SESSION['user_id']   = $user['id']
      → $_SESSION['full_name'] = $user['full_name']
      → $_SESSION['role']      = $user['role']
      → header('Location: dashboard.php')
```

---

## 9. Installation Guide

### Step 1 — Copy Files
Place the entire project folder inside XAMPP's htdocs:
```
C:\xampp\htdocs\SystemINTEG-ADMS\
```

### Step 2 — Start XAMPP
Open XAMPP Control Panel → Start **Apache** and **MySQL**.

### Step 3 — Import the Database
1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Click **New** → create database named `clinic_inventory`
3. Select the `clinic_inventory` database
4. Click **Import** tab
5. Choose file: `clinic-inventory/config/clinic_inventory.sql`
6. Click **Go**

### Step 4 — Set Passwords (REQUIRED)
Open your browser and go to:
```
http://localhost/SystemINTEG-ADMS/production/setup.php
```
You will see:
```
✅ Password set for admin
✅ Password set for staff1
⚠ DELETE this file after running it!
```
**Delete `setup.php` immediately after.**

### Step 5 — Login
Go to:
```
http://localhost/SystemINTEG-ADMS/production/login.php
```

---

## 10. Default Accounts

| Role  | Username | Password  | Access                              |
|-------|----------|-----------|-------------------------------------|
| Admin | `admin`  | `admin123`| Full access including User Management |
| Staff | `staff1` | `staff123`| Inventory, Transactions, Reports    |

> Change these passwords after your first login via User Management.

---

## 11. Page-by-Page Guide

### `login.php`
- **URL**: `/production/login.php`
- **What it does**: Authenticates the user. On success, starts a session and redirects to dashboard.
- **Form fields**: `txtUsername`, `txtPassword`
- **Submit button**: `btnLogin`

### `dashboard.php`
- **URL**: `/production/dashboard.php`
- **What it does**: Shows live inventory statistics. All numbers come from MySQL queries, not hardcoded values.
- **Key queries**: COUNT of items, SUM of quantity, COUNT of low/out/expiring items, last 10 transactions.

### `inventory.php`
- **URL**: `/production/inventory.php`
- **Actions via URL/POST**:
  - Default → list table
  - `?actionEdit=5` → edit form for item ID 5
  - `?actionDelete=5` → delete item ID 5
  - `POST btnAdd` → show add form
  - `POST btnSave` → insert new item
  - `POST btnUpdate` → update existing item
- **Important**: Quantity on the edit form is **read-only**. Only Stock In / Stock Out changes quantity.

### `categories.php`
- **URL**: `/production/categories.php`
- Same action pattern as `inventory.php`.
- Prevents deleting a category that has inventory items linked to it.

### `stock_in.php`
- **URL**: `/production/stock_in.php`
- **Form fields**: `cmbItem` (dropdown), `txtQuantity`, `txtRemarks`
- **Submit button**: `btnStockIn`
- Uses MySQL transaction. Increases `inventory.quantity` and writes to `inventory_transactions`.

### `stock_out.php`
- **URL**: `/production/stock_out.php`
- **Form fields**: `cmbItem`, `txtQuantity`, `txtRemarks`
- **Submit button**: `btnStockOut`
- Validates that `quantity entered ≤ current stock`. Uses MySQL transaction.
- JavaScript gives real-time stock feedback as you select an item.

### `transactions.php`
- **URL**: `/production/transactions.php`
- **Filter fields**: `search`, `type`, `date_from`, `date_to`
- Shows every stock movement with date, user, item, and remarks.

### `reports.php`
- **URL**: `/production/reports.php`
- **Report type**: `?report=inventory` or `?report=transactions`
- **Quick filters**: `?filter=low_stock`, `?filter=out_of_stock`, `?filter=expiring`
- Print: `window.print()` button hides nav and prints the table.

### `users.php`
- **URL**: `/production/users.php`
- **Admin only** — staff is redirected to dashboard if they try to access it.
- **Actions**: view, add (`btnAdd`/`btnSave`), edit (`actionEdit`/`btnUpdate`), toggle status (`actionToggle`), delete (`actionDelete`).
- Password field on edit form is optional — leave blank to keep existing password.

---

## Summary Checklist

| Feature                            | Status |
|------------------------------------|--------|
| Login with session                 | ✅     |
| Logout                             | ✅     |
| Password hashing (bcrypt)          | ✅     |
| Role-based access control          | ✅     |
| Dashboard with live DB stats       | ✅     |
| Inventory list with search/filter  | ✅     |
| Add inventory item                 | ✅     |
| Edit inventory item                | ✅     |
| Delete inventory item (with guard) | ✅     |
| Category management                | ✅     |
| Stock In (MySQL transaction)       | ✅     |
| Stock Out (stock validation)       | ✅     |
| Transaction history with filters   | ✅     |
| Reports (inventory + transactions) | ✅     |
| Print report                       | ✅     |
| Low stock alerts                   | ✅     |
| Expiring items alerts              | ✅     |
| User management (Admin only)       | ✅     |
| Asia/Manila timezone               | ✅     |
| Prepared statements (SQL injection protection) | ✅ |

---

*Clinic Inventory Management System — built on Gentelella v4 Admin Dashboard Template*
