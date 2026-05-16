<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\ExportsController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\FileAttachmentController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CreditCustomerController;

Route::get('/', [SystemController::class, 'home']);

Route::get('/login', [\App\Controllers\AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [\App\Controllers\AuthController::class, 'login']);
Route::get('/logout', [\App\Controllers\AuthController::class, 'logout']);
Route::post('/logout', [\App\Controllers\AuthController::class, 'logout']);

Route::middleware(['require.login', 'license.check'])->group(function () {
    Route::get('/search', [GlobalSearchController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/checks', [DashboardController::class, 'checks']);

    // Module hub and core module routes
    Route::get('/module-hub', [ModulesController::class, 'index']);
    Route::get('/expenses', [ExpensesController::class, 'index']);
    Route::post('/expenses', [ExpensesController::class, 'store']);
    Route::get('/expenses/{id}/edit', [ExpensesController::class, 'edit']);
    Route::put('/expenses/{id}', [ExpensesController::class, 'update']);
    Route::delete('/expenses/{id}', [ExpensesController::class, 'destroy']);
    Route::get('/journal-entries', [JournalController::class, 'index']);
    Route::post('/journal-entries', [JournalController::class, 'store']);
    Route::get('/journal-entries/{id}/edit', [JournalController::class, 'edit']);
    Route::put('/journal-entries/{id}', [JournalController::class, 'update']);
    Route::delete('/journal-entries/{id}', [JournalController::class, 'destroy']);

    // Sales operations
    Route::get('/sales', [OperationsController::class, 'sales']);
    Route::get('/sales/financial-statement', [OperationsController::class, 'financialStatement']);
    Route::get('/sales/financial-statement/export/csv', [ExportsController::class, 'financialStatementCsv']);
    Route::get('/sales/financial-statement/export/pdf', [ExportsController::class, 'financialStatementPdf']);
    Route::get('/sales/export/csv', [ExportsController::class, 'salesCsv']);
    Route::get('/sales/export/pdf', [ExportsController::class, 'salesPdf']);
    Route::get('/sales/general-ledger', [OperationsController::class, 'generalLedger']);
    Route::get('/sales/general-ledger/export/csv', [ExportsController::class, 'generalLedgerCsv']);
    Route::get('/sales/general-ledger/export/pdf', [ExportsController::class, 'generalLedgerPdf']);
    Route::get('/reports', [OperationsController::class, 'reports']);
    Route::get('/reports/sales', [OperationsController::class, 'reportSales']);
    Route::get('/reports/revenue', [OperationsController::class, 'reportRevenue']);
    Route::get('/reports/expenses', [OperationsController::class, 'reportExpenses']);
    Route::get('/reports/balance', [OperationsController::class, 'reportBalance']);
    Route::get('/reports/sales/export/csv', [ExportsController::class, 'reportSalesCsv']);
    Route::get('/reports/sales/export/pdf', [ExportsController::class, 'reportSalesPdf']);
    Route::get('/reports/revenue/export/csv', [ExportsController::class, 'reportRevenueCsv']);
    Route::get('/reports/revenue/export/pdf', [ExportsController::class, 'reportRevenuePdf']);
    Route::get('/reports/expenses/export/csv', [ExportsController::class, 'reportExpensesCsv']);
    Route::get('/reports/expenses/export/pdf', [ExportsController::class, 'reportExpensesPdf']);
    Route::get('/reports/balance/export/pdf', [ExportsController::class, 'reportBalancePdf']);
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::post('/settings', [SettingsController::class, 'update']);
    Route::get('/settings/license', [SettingsController::class, 'license']);
    Route::post('/settings/license/issue', [SettingsController::class, 'issueLicense']);
    Route::post('/settings/license/company-status', [SettingsController::class, 'toggleTenantStatus']);
    Route::post('/settings/license/delete-tenant', [SettingsController::class, 'deleteTenant']);
    Route::post('/settings/license/send-reset', [SettingsController::class, 'sendTenantReset']);
    Route::post('/settings/license/create-tenant', [SettingsController::class, 'createTenant']);
    Route::post('/settings/license/update-email', [SettingsController::class, 'updateTenantEmail']);
    Route::get('/settings/license/edit/{companyId}', [SettingsController::class, 'editTenant']);
    Route::post('/settings/license/update-tenant', [SettingsController::class, 'updateTenant']);
    Route::get('/settings/license/history/{companyId}', [SettingsController::class, 'licenseHistory']);
    Route::get('/setup', [OperationsController::class, 'setup']);
    Route::post('/setup/health-check', [OperationsController::class, 'setupHealthCheck']);
    Route::post('/setup/test-email', [OperationsController::class, 'setupSendTestEmail']);

    // Credit management
    Route::get('/credit-management', [OperationsController::class, 'creditManagement']);
    Route::post('/credit-management/issue', [OperationsController::class, 'creditIssue']);
    Route::post('/credit-management/payment', [OperationsController::class, 'creditPayment']);
    Route::post('/credit-management/write-off', [OperationsController::class, 'creditWriteOff']);
    Route::post('/credit-management/reopen', [OperationsController::class, 'creditReopen']);
    Route::get('/credit-management/view', [OperationsController::class, 'creditView']);
    Route::get('/credit-management/agreement', [OperationsController::class, 'creditAgreement']);
    Route::get('/credit-management/export/csv', [ExportsController::class, 'creditsCsv']);
    Route::get('/credit-management/export/pdf', [ExportsController::class, 'creditsPdf']);

    // Credit Customers
    Route::get('/credit-customers', [CreditCustomerController::class, 'index']);
    Route::get('/credit-customers/create', [CreditCustomerController::class, 'create']);
    Route::post('/credit-customers', [CreditCustomerController::class, 'store']);
    Route::get('/credit-customers/{id}', [CreditCustomerController::class, 'show']);
    Route::get('/credit-customers/{id}/edit', [CreditCustomerController::class, 'edit']);
    Route::put('/credit-customers/{id}', [CreditCustomerController::class, 'update']);
    Route::delete('/credit-customers/{id}', [CreditCustomerController::class, 'destroy']);
    Route::post('/credit-customers/{id}/toggle-status', [CreditCustomerController::class, 'toggleStatus']);

    Route::get('/clients/export', [ClientController::class, 'export'])->name('clients.export');
    Route::post('/clients/{id}/toggle-status', [ClientController::class, 'toggleStatus']);
    Route::resource('clients', ClientController::class);
    Route::resource('products', ProductController::class);
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('estimates', EstimateController::class);
    Route::get('/estimates/{id}/pdf', [EstimateController::class, 'pdf'])->name('estimates.pdf');

    Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('/invoices/{id}/paid', [InvoiceController::class, 'updateStatus'])->name('invoices.paid');
    Route::post('/invoices/{id}/items', [InvoiceController::class, 'addItem'])->name('invoices.items.add');
    Route::get('/invoices/{invoice}/items/{item}/edit', [InvoiceController::class, 'editItem'])->name('invoices.items.edit');
    Route::patch('/invoices/{invoice}/items/{item}', [InvoiceController::class, 'updateItem'])->name('invoices.items.update');
    Route::delete('/invoices/{invoice}/items/{item}', [InvoiceController::class, 'deleteItem'])->name('invoices.items.delete');
    Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // convert
    Route::get('/estimates/{id}/convert', [EstimateController::class, 'convert']);

    // Inventory module (dynamic)
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory/move', [InventoryController::class, 'move']);
    Route::get('/inventory/audit', [InventoryController::class, 'audit']);
    Route::get('/inventory/export/csv', [ExportsController::class, 'inventoryCsv']);
    Route::get('/inventory/export/pdf', [ExportsController::class, 'inventoryPdf']);
    Route::get('/inventory/audit/export/csv', [ExportsController::class, 'inventoryAuditCsv']);
    Route::get('/inventory/audit/export/pdf', [ExportsController::class, 'inventoryAuditPdf']);

    // Audit logs (dynamic)
    Route::get('/audit', [AuditTrailController::class, 'index']);

    // User management
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users/invite', [UserController::class, 'invite']);
    Route::post('/users/invite/{id}/cancel', [UserController::class, 'cancelInvitation']);
    Route::post('/users/{id}/toggle', [UserController::class, 'toggleStatus']);
    Route::post('/users/{id}/role', [UserController::class, 'updateRole']);
    Route::post('/users/{id}/reset-password', [UserController::class, 'sendPasswordReset']);

    // Profile & notifications
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/password', [ProfileController::class, 'changePassword']);
    Route::post('/profile/preferences', [ProfileController::class, 'updatePreferences']);
    Route::post('/profile/delete', [ProfileController::class, 'deleteAccount']);
    Route::get('/notifications', [ProfileController::class, 'notifications']);

    // Recurring invoices
    Route::get('/recurring-invoices', [RecurringInvoiceController::class, 'index']);
    Route::get('/recurring-invoices/create', [RecurringInvoiceController::class, 'create']);
    Route::post('/recurring-invoices', [RecurringInvoiceController::class, 'store']);
    Route::get('/recurring-invoices/{id}', [RecurringInvoiceController::class, 'show']);
    Route::post('/recurring-invoices/{id}/toggle', [RecurringInvoiceController::class, 'toggleStatus']);
    Route::delete('/recurring-invoices/{id}', [RecurringInvoiceController::class, 'destroy']);

    // Tax rates
    Route::get('/tax-rates', [TaxRateController::class, 'index']);
    Route::post('/tax-rates', [TaxRateController::class, 'store']);
    Route::put('/tax-rates/{id}', [TaxRateController::class, 'update']);
    Route::post('/tax-rates/{id}/toggle', [TaxRateController::class, 'toggleActive']);
    Route::delete('/tax-rates/{id}', [TaxRateController::class, 'destroy']);

    // CSV Import
    Route::get('/import', [ImportController::class, 'index']);
    Route::post('/import/clients', [ImportController::class, 'importClients']);
    Route::post('/import/products', [ImportController::class, 'importProducts']);

    // File Attachments
    Route::get('/attachments', [FileAttachmentController::class, 'index']);
    Route::post('/attachments', [FileAttachmentController::class, 'upload']);
    Route::get('/attachments/{id}/download', [FileAttachmentController::class, 'download']);
    Route::delete('/attachments/{id}', [FileAttachmentController::class, 'destroy']);

    // Database Backups (admin only — operations protected inside controller)
    Route::get('/settings/backups', [BackupController::class, 'index']);
    Route::post('/settings/backups', [BackupController::class, 'store']);
    Route::post('/settings/backups/tenant', [BackupController::class, 'storeTenant']);
    Route::post('/settings/backups/restore', [BackupController::class, 'restore']);
    Route::post('/settings/backups/tenant/restore', [BackupController::class, 'restoreTenant']);
    Route::post('/settings/backups/upload', [BackupController::class, 'upload']);
    Route::post('/settings/backups/delete', [BackupController::class, 'destroy']);
    Route::post('/settings/backups/destroy-all', [BackupController::class, 'destroyAll']);
    Route::get('/settings/backups/download/{filename}', [BackupController::class, 'download'])
        ->where('filename', '.+');
});

// Short alias to avoid dashboard /license 404 when route is now /license-required
Route::get('/license', [SystemController::class, 'licenseAlias']);

// Invitation acceptance (public, no auth required)
Route::get('/invite/accept', [UserController::class, 'acceptForm']);
Route::post('/invite/accept', [UserController::class, 'acceptStore']);

// License required (dynamic status + display values)
Route::get('/register', [\App\Controllers\AuthController::class, 'showRegister']);
Route::post('/register', [\App\Controllers\AuthController::class, 'register']);
Route::get('/forgot-password', [\App\Controllers\AuthController::class, 'showForgotPassword']);
Route::post('/forgot-password', [\App\Controllers\AuthController::class, 'forgotPassword']);
Route::get('/reset-password', [\App\Controllers\AuthController::class, 'showResetPassword']);
Route::post('/reset-password', [\App\Controllers\AuthController::class, 'resetPassword']);

Route::get('/license-required', [SystemController::class, 'licenseRequired']);

// General fallback for non-existing routes
Route::fallback([SystemController::class, 'notFound']);

// Company/subdomain registration
Route::get('/register-company', function() {
    return view('auth.register_company');
});
Route::post('/register-company', [SystemController::class, 'registerCompany']);
