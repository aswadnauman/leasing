# Copilot Instructions for Lease Management System

## Project Overview
A PHP-based multi-outlet lease management system with integrated accounts and inventory modules. Built on XAMPP stack (Apache, MySQL, PHP), using procedural PHP with MySQLi prepared statements.

## Architecture

### Core Modules
- **Leasing Core**: Clients, Leases, Installment Schedules, Lease Payments, Recovery Collections
- **Master Data**: Outlets, Users, Products, Master Lists (profession, area, road, city)
- **Accounts**: Chart of Accounts, Payment/Receipt Vouchers, Journal Entries
- **Inventory**: Product tracking, Stock Adjustments, Supplier/Customer Management
- **Reporting**: Multi-outlet consolidated reports, Recovery/Sales/Expense analysis

### Multi-Outlet Design
All operational tables include `outlet_id` foreign key. Key operations:
- Lease/Payment scoped by outlet
- Inventory/Accounts auto-synchronize across outlets
- Reporting filters by outlet with consolidated view option
- User role assignments: `BranchManager` (outlet-specific), `RecoveryOfficer`, `AccountsOfficer`, `DataEntry`

### Data Flow
1. **Lease Creation** → `leases` + `installment_schedule` auto-generation
2. **Payment** → Updates `installment_schedule`, `lease_payments`, `leases.outstanding_amount`
3. **Collection** → `recovery_collections` with role-based approval (`BranchManager` → `AccountsOfficer` → `Admin`)
4. **Accounts Posting** → `journal_entries` + `journal_entry_lines` (from payments/vouchers)
5. **Audit Trail** → `audit_trail` logs all CRUD operations by user

## Critical Conventions

### Database Interaction
- **Connection**: `$conn = getDBConnection()` from `config/db.php` (MySQLi)
- **Prepared Statements Only**: Always use `$conn->prepare()` with bind_param for security
- **Transactions**: Use `$conn->begin_transaction()`, `commit()`, `rollback()` for multi-step operations (see `payment_collection.php` example)
- **ID Generation**: Manual IDs (e.g., `$lease_id = "LS" . date('Ymd') . rand(1000, 9999)`) not auto-increment

### AJAX Search Pattern
All master data searches use standardized endpoint in `includes/dynamic_dropdowns.php`:
- **Endpoint**: `?action=search_master&type=<entity>&q=<search_term>`
- **Returns**: `{ results: [{ id, text }] }` (Select2 compatible)
- **Supported Types**: `profession`, `area`, `road`, `city`, `recovery_person`, `client`, `product`, `lease`
- Used in forms for dynamic searchable dropdowns

### Image Upload Pattern
Photos stored in `uploads/{entity}/` (e.g., `uploads/clients/`, `uploads/recovery_persons/`):
- Destination: `photo_path` column in entity table
- Validation: File type (jpg/png), max 500KB
- Compression: Attempts JPEG downgrade to 20KB (see `clients.php` lines 78-142)
- Display: HTML `<img src="{photo_path}">` with fallback placeholder

### Session & Auth
- **Session Start**: `session_start()` at top of every page
- **Auth Check**: Redirect to `login.php` if `!isset($_SESSION['user_id'])`
- **Role Check**: `$_SESSION['role']` (Admin/BranchManager/RecoveryOfficer/AccountsOfficer/DataEntry/Sales)
- **User ID**: `$_SESSION['user_id']` passed to created_by/updated_by fields

### Form Submission Pattern
```php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_entity'])) { /* INSERT */ }
    elseif (isset($_POST['update_entity'])) { /* UPDATE */ }
    elseif (isset($_POST['delete_entity'])) { /* DELETE */ }
}
```
Button names: `add_<entity>`, `update_<entity>`, `delete_<entity>` — checked via `isset()`

## File Organization

| Directory | Purpose |
|-----------|---------|
| `config/` | DB connection, base schema |
| `includes/` | Shared functions: dynamic_dropdowns, sidebar, menus |
| `assets/css/` | Bootstrap + custom styles |
| `assets/js/` | Chart.js, Select2 integrations |
| `uploads/` | Client/recovery person photos |
| Root `*.php` | Modules (clients, leases, payments, etc.) |
| `docs/` | Navigation structure docs |

## Key Integration Points

### Installment Auto-Generation
Called in `leases.php` on lease add:
```php
generateInstallmentPayments($conn, $lease_id, $start_date, $end_date, $number_of_installments, $installment_amount);
```
Generates rows in `installment_schedule` with status 'Pending'.

### Approval Workflows
- **Collections** → `recovery_collections.approval_status` (Pending → Supervisor Approved → Final Approved)
- **Payments** → may require supervisory sign-off depending on amount

### Report Queries
Reports join multiple tables (leases, clients, products, payments, recovery_collections, chart_of_accounts) and filter by:
- `outlet_id`, `area`, `road`, `recovery_person_id`, date range
- Often use `GROUP BY` for summaries, `SUM()` for totals

## Common Patterns to Apply

1. **Search Form + Results**: Separate `$_GET` params for filters, build `WHERE` dynamically (see `leases.php` lines 85-112)
2. **Record Relationships**: Always include `outlet_id` scope; cascade deletes via foreign keys
3. **Status Tracking**: Entities have `status` field (Active/Inactive, Pending/Paid, etc.); update via `UPDATE` statements
4. **Timestamps**: `created_at` and `updated_at` auto-managed by `DEFAULT CURRENT_TIMESTAMP`
5. **Modal Forms**: Edit forms often use hidden inputs for existing data, same form for add+update

## Testing Workflow
1. Verify DB connection: `check_db.php` confirms schema
2. Seed demo data: `config/database.sql` includes fixtures
3. Manual testing: Navigate through CRUD flows via browser
4. Check audit trail: `audit_trail.php` validates all operations logged

## External Dependencies
- Bootstrap 5.3 (CDN)
- Select2 (for searchable dropdowns)
- Chart.js (for reporting graphs)
- No ORM or framework — pure procedural PHP

## Performance Considerations
- Add indexes on frequently queried columns (done in schema for outlet_id, lease_id, etc.)
- Limit AJAX search results to 20 records
- Photo compression keeps uploads light
- Use prepared statements to avoid query recompilation
