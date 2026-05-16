<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Skyare REST API v1 (tenant-scoped via subdomain)
Route::prefix('v1')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [ApiController::class, 'dashboardStats']);

    // Clients
    Route::get('/clients', [ApiController::class, 'clientsIndex']);
    Route::get('/clients/{id}', [ApiController::class, 'clientsShow']);
    Route::post('/clients', [ApiController::class, 'clientsStore']);

    // Products
    Route::get('/products', [ApiController::class, 'productsIndex']);
    Route::get('/products/{id}', [ApiController::class, 'productsShow']);
    Route::post('/products', [ApiController::class, 'productsStore']);

    // Invoices
    Route::get('/invoices', [ApiController::class, 'invoicesIndex']);
    Route::get('/invoices/{id}', [ApiController::class, 'invoicesShow']);

    // Payments
    Route::get('/payments', [ApiController::class, 'paymentsIndex']);

    // Expenses
    Route::get('/expenses', [ApiController::class, 'expensesIndex']);
});
