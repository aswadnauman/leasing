# Lease Management System

Fully integrated leasing application with Accounts and Inventory modules. Supports multi‑outlet operations, real‑time updates, and complete CRUD flows across client, lease, recovery, payment, and reporting features.

## Objective
- Deliver an organized, integrated system ready for deployment, supporting multi‑product leases, outlet synchronization, and smooth day‑to‑day operations.

## Key Features
- Project analysis, cleanup, and directory alignment
- Verified database connection and CRUD across modules
- Robust schema with `installment_schedule` and payment linkage
- Dynamic menu and searchable dropdowns for master data
- Recovery & Collections with approval workflow
- Accounts and Inventory integration for auto postings and stock tracking
- Multi‑outlet support with consolidated reporting

## Project Structure
- `config/` — connection and base schema (`database.sql`)
- `includes/` — shared UI, menu, and dropdown helpers
- `assets/` — CSS/JS for UI and interactions
- `reports/` — operational and analysis reports
- `uploads/` — client and recovery person photos
- Root `*.php` — modules and pages (clients, leases, payments, inventory, accounts)

## Database Schema
- Base schema: `config/database.sql`
- Extensions: `database_extensions.sql`
- Core tables: `outlets`, `users`, `master_city`, `master_area`, `master_road`, `master_profession`, `products`, `clients`, `recovery_persons`
- Leasing: `leases`, `installment_schedule`, `lease_payments`, `audit_trail`, `system_config`
- Recovery: `recovery_collections`
- Accounts & Inventory: `chart_of_accounts`, `payment_vouchers`, `receipt_vouchers`, `journal_entries`, `journal_entry_lines`, `inventory_transactions`, plus `suppliers`, `customers`, `sales`, `sales_items`, `sales_returns`, `sales_return_items`, `purchases`, `purchase_items`, `purchase_returns`, `purchase_return_items`
- Indexes added for performance on frequently filtered columns

## Setup
- Start MySQL/Apache in XAMPP
- Import base schema: `config/database.sql` (via phpMyAdmin or `mysql` CLI)
- Import extensions: `database_extensions.sql`
- Configure connection in `config/db.php`
- Open `setup.php` in browser to initialize menus and verify environment

## Modules to Test
- Client & Guarantor Management
- Lease Registration & Assignment (multiple products per lease supported via schedule and itemization)
- Recovery & Collection (field collections with verification and approvals)
- Payment Management (cash/bank/online, approval path)
- Master Settings (profession, area, road, city, recovery person with photo)
- Reporting & Analysis (area/road/recovery‑wise, overdue/outstanding)
- Accounts & Inventory Integration (auto postings, outlet synchronization, inventory deductions)

## Searchable Dropdowns
- Master data AJAX search implemented in `includes/dynamic_dropdowns.php`
- GET: `action=search_master&type=<entity>&q=<term>` returns `{ results: [{ id, text }] }`

## Validation Checklist
- Links and references open correct pages, no dead anchors
- All CRUD flows (Add/Edit/Update/View/Delete) succeed and persist in DB
- Installments render from `installment_schedule`; payments update schedule and outstanding
- Recovery collections record positions and amounts; supervisor/accounts approvals flow
- Reports load and filter by outlet, area, road, recovery person
- Accounts and Inventory pages post entries and track stock movement

## Multi‑Outlet Operations
- All operational tables key by `outlet_id`
- Consolidated reporting across outlets with per‑outlet filters
- Inventory and accounts synchronized between outlets and main branch

## Demo Data
- Seed data for outlets, users, products, clients, master data, and recovery persons included in `config/database.sql`

## Notes
- For deployment, ensure writable permissions on `uploads/`
- Review `system_config` entries for company name, currency, late fee, grace period, backup frequency
