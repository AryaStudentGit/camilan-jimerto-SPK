<?php

use App\Http\Controllers\AhpController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalculationRunController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SalesRecapController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Quick Login switcher (handy for testing & demo)
Route::get('/quick-login/{role}', [AuthController::class, 'quickLogin'])->name('quick-login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes (Both Admin and Pemilik UMKM)
Route::middleware('auth')->group(function () {
    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Interactive AJAX Drill-down & What-If Simulator APIs
    Route::get('/api/drilldown/{productId}', [DashboardController::class, 'drilldown'])->name('api.drilldown');
    Route::get('/api/criterion-drilldown/{criterionId}', [DashboardController::class, 'criterionDrilldown'])->name('api.criterion-drilldown');
    Route::post('/api/simulate', [DashboardController::class, 'simulate'])->name('api.simulate');

    // AHP Pairwise Comparison & Weights (Owner & Admin)
    Route::get('/ahp', [AhpController::class, 'index'])->name('ahp.index');
    Route::post('/ahp/{runId}', [AhpController::class, 'update'])->name('ahp.update');

    // Transactions Explorer (Accessible to both)
    Route::get('/sales-recaps/transactions', [SalesRecapController::class, 'transactions'])->name('sales-recaps.transactions');

    // Admin Only Management Routes
    Route::middleware('role:admin')->group(function () {
        // Monthly Sales Recap Matrix & CSV Import
        Route::get('/sales-recaps', [SalesRecapController::class, 'index'])->name('sales-recaps.index');
        Route::get('/sales-recaps/template', [SalesRecapController::class, 'downloadTemplate'])->name('sales-recaps.template');
        Route::post('/sales-recaps', [SalesRecapController::class, 'store'])->name('sales-recaps.store');
        Route::post('/sales-recaps/import', [SalesRecapController::class, 'import'])->name('sales-recaps.import');

        // Operational Assessments (C2, C3, C4, C5)
        Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::post('/assessments/{runId}', [AssessmentController::class, 'update'])->name('assessments.update');

        // Calculation Runs / Periods Management
        Route::get('/runs', [CalculationRunController::class, 'index'])->name('runs.index');
        Route::post('/runs', [CalculationRunController::class, 'store'])->name('runs.store');
        Route::post('/runs/{id}/activate', [CalculationRunController::class, 'activate'])->name('runs.activate');
        Route::delete('/runs/{id}', [CalculationRunController::class, 'destroy'])->name('runs.destroy');

        // Master Data Products & Criteria
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
        Route::get('/criteria', [CriterionController::class, 'index'])->name('criteria.index');
        Route::put('/criteria/{id}', [CriterionController::class, 'update'])->name('criteria.update');
    });
});
