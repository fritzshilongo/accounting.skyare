# Skyare Module Completeness Checklist (DB + Controllers)

This checklist targets the modules requested for production readiness:

- Dashboard
- Clients
- Invoices
- Products
- Payments
- Estimates
- Inventory
- Audit
- Settings

## 1) Critical DB Alignment SQL

Run on MariaDB for `sumbkqqz_skyare_main_db`.

```sql
-- 1. companies table required by tenant resolution and registration
CREATE TABLE IF NOT EXISTS companies (
  company_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(255) NOT NULL,
  subdomain VARCHAR(100) NOT NULL UNIQUE,
  status VARCHAR(50) NOT NULL DEFAULT 'active',
  registration_number VARCHAR(100) NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(255) NULL,
  address VARCHAR(500) NULL,
  city VARCHAR(100) NULL,
  province VARCHAR(100) NULL,
  postal_code VARCHAR(20) NULL,
  country VARCHAR(100) NULL,
  tax_number VARCHAR(100) NULL,
  vat_number VARCHAR(100) NULL,
  logo_data LONGTEXT NULL,
  bank_name VARCHAR(255) NULL,
  bank_account_holder VARCHAR(255) NULL,
  bank_account_number VARCHAR(100) NULL,
  bank_routing_number VARCHAR(100) NULL,
  bank_swift_code VARCHAR(100) NULL,
  bank_iban VARCHAR(100) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. password_resets table required by custom AuthController reset flow
CREATE TABLE IF NOT EXISTS password_resets (
  reset_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_password_resets_user_id (user_id),
  INDEX idx_password_resets_token (token)
);

-- 3. role_permissions table required for dynamic RBAC (non-admin roles)
CREATE TABLE IF NOT EXISTS role_permissions (
  permission_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  role_key VARCHAR(50) NOT NULL,
  module_key VARCHAR(100) NOT NULL,
  can_view TINYINT(1) NOT NULL DEFAULT 1,
  can_create TINYINT(1) NOT NULL DEFAULT 0,
  can_edit TINYINT(1) NOT NULL DEFAULT 0,
  can_delete TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_role_module (company_id, role_key, module_key)
);

-- 4. inventory_movements optional: app now supports fallback to legacy inventory table,
-- but creating this aligns with current model design.
CREATE TABLE IF NOT EXISTS inventory_movements (
  movement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  movement_type VARCHAR(50) NOT NULL,
  quantity DECIMAL(14,2) NOT NULL,
  qty_before DECIMAL(14,2) NOT NULL DEFAULT 0,
  qty_after DECIMAL(14,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_inventory_movements_company (company_id),
  INDEX idx_inventory_movements_product (product_id)
);
```

## 2) Controller/Route Readiness by Module

## Dashboard
- Controller: `App\Controllers\DashboardController`
- Route: `/dashboard`
- Status: patched for MySQL date functions and fail-safe metrics when optional tables are missing.
- Verify: dashboard loads without SQL exceptions.

## Clients
- Controller: `App\Http\Controllers\ClientController`
- Route: `/clients`
- DB table used: `clients`
- Verify CRUD, export, and status filtering.

## Invoices
- Controller: `App\Http\Controllers\InvoiceController`
- Route: `/invoices`
- DB tables used: `invoices`, `invoice_items`, `clients`
- Verify: create invoice, add items, mark paid, PDF export.

## Products
- Controller: `App\Http\Controllers\ProductController`
- Route: `/products`
- DB table used: `products`
- Status: Product model patched for compatibility with both modern and legacy callers.

## Payments
- Controller: `App\Http\Controllers\PaymentController`
- Route: `/payments`
- DB tables used: `payments`, `invoices`
- Verify payment creation updates invoice status.

## Estimates
- Controller: `App\Http\Controllers\EstimateController`
- Route: `/estimates`
- DB tables used: `estimates`, `estimate_items`

## Inventory
- Controller: `App\Controllers\InventoryController`
- Route: `/inventory`, `/inventory/audit`
- Export routes:
  - `/inventory/export/csv`
  - `/inventory/export/pdf`
  - `/inventory/audit/export/csv`
  - `/inventory/audit/export/pdf`
- DB: patched to support both `inventory_movements` and fallback `inventory` table.

## Audit
- Controller: `App\Controllers\AuditTrailController`
- Route: `/audit`
- DB table used: `audit_logs`

## Settings
- Controller: `App\Controllers\SettingsController`
- Route: `/settings`
- Purpose: support mailbox and tenant base-domain visibility.

## 3) Server Verification Commands

```bash
cd ~/skyare-laravel
php artisan migrate --force
php artisan db:seed --force
# or explicitly:
php artisan db:seed --class="Database\\Seeders\\DevAccessSeeder" --force
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache -v
```

If `route:cache` fails, run:

```bash
php artisan route:list | grep Closure
```

Expected result after current patch: no closure routes.

## Development Login Bootstrap

Seeder class: `Database\\Seeders\\DevAccessSeeder`

Default credentials (override via .env):

- Email: `devadmin@skyare.space`
- Password: `DevAdmin@12345`
- Subdomain: `www`

## 4) Smoke Test URLs

- `/login`
- `/register`
- `/license-required`
- `/dashboard`
- `/clients`
- `/invoices`
- `/products`
- `/payments`
- `/estimates`
- `/inventory`
- `/inventory/audit`
- `/audit`
- `/settings`
- `/reports`

## 5) Asset Check (Logo)

Confirm this file exists on server:

- `public/assets/images/skyare-logo.png`

Views now reference this path consistently.
