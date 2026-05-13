<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Masters\FirmController;
use App\Http\Controllers\Masters\BranchController;
use App\Http\Controllers\Masters\CategoryController;
use App\Http\Controllers\Masters\UomController;
use App\Http\Controllers\Masters\TaxController;
use App\Http\Controllers\Masters\PartyController;
use App\Http\Controllers\Masters\ProductController;
use App\Http\Controllers\Masters\DesignController;
use App\Http\Controllers\Masters\StockController;
use App\Http\Controllers\Transactions\PurchaseController;
use App\Http\Controllers\Transactions\PurchaseReturnController;
use App\Http\Controllers\Transactions\SaleController;
use App\Http\Controllers\Transactions\OrderController;
use App\Http\Controllers\Transactions\SaleReturnController;
use App\Http\Controllers\Utilities\UserController;
use App\Http\Controllers\Utilities\SysParaController;
use App\Http\Controllers\Utilities\FinancialYearController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('web')->group(function () {
    Route::get('/',        [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',  [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Protected routes
Route::middleware(['web', 'auth.check'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Masters ──────────────────────────────────────────────────────────────
    Route::prefix('masters')->name('masters.')->group(function () {

        // Firm Info (single record — no delete)
        Route::get   ('firm',          [FirmController::class, 'index']) ->name('firm.index');
        Route::post  ('firm',          [FirmController::class, 'store']) ->name('firm.store');
        Route::put   ('firm/{id}',     [FirmController::class, 'update'])->name('firm.update');

        // Branch
        Route::get   ('branch',        [BranchController::class, 'index'])  ->name('branch.index');
        Route::post  ('branch',        [BranchController::class, 'store'])  ->name('branch.store');
        Route::put   ('branch/{id}',   [BranchController::class, 'update']) ->name('branch.update');
        Route::delete('branch/{id}',   [BranchController::class, 'destroy'])->name('branch.destroy');

        // Category
        Route::get   ('category',      [CategoryController::class, 'index'])  ->name('category.index');
        Route::post  ('category',      [CategoryController::class, 'store'])  ->name('category.store');
        Route::put   ('category/{id}', [CategoryController::class, 'update']) ->name('category.update');
        Route::delete('category/{id}', [CategoryController::class, 'destroy'])->name('category.destroy');

        // UOM
        Route::get   ('uom',           [UomController::class, 'index'])  ->name('uom.index');
        Route::post  ('uom',           [UomController::class, 'store'])  ->name('uom.store');
        Route::put   ('uom/{id}',      [UomController::class, 'update']) ->name('uom.update');
        Route::delete('uom/{id}',      [UomController::class, 'destroy'])->name('uom.destroy');

        // Tax
        Route::get   ('tax',           [TaxController::class, 'index'])  ->name('tax.index');
        Route::post  ('tax',           [TaxController::class, 'store'])  ->name('tax.store');
        Route::put   ('tax/{id}',      [TaxController::class, 'update']) ->name('tax.update');
        Route::delete('tax/{id}',      [TaxController::class, 'destroy'])->name('tax.destroy');

        // Party
        Route::get   ('party',          [PartyController::class, 'index'])  ->name('party.index');
        Route::get   ('party/create',   [PartyController::class, 'create']) ->name('party.create');
        Route::post  ('party',          [PartyController::class, 'store'])  ->name('party.store');
        Route::get   ('party/{id}/edit',[PartyController::class, 'edit'])   ->name('party.edit');
        Route::put   ('party/{id}',     [PartyController::class, 'update']) ->name('party.update');
        Route::delete('party/{id}',     [PartyController::class, 'destroy'])->name('party.destroy');

        // Product (mat_code is string PK — use matCode param)
        Route::get   ('product',              [ProductController::class, 'index'])  ->name('product.index');
        Route::post  ('product',              [ProductController::class, 'store'])  ->name('product.store');
        Route::put   ('product/{matCode}',    [ProductController::class, 'update']) ->name('product.update');
        Route::delete('product/{matCode}',    [ProductController::class, 'destroy'])->name('product.destroy');

        // Design
        Route::get   ('design',               [DesignController::class, 'index'])  ->name('design.index');
        Route::post  ('design',               [DesignController::class, 'store'])  ->name('design.store');
        Route::put   ('design/{id}',          [DesignController::class, 'update']) ->name('design.update');
        Route::delete('design/{id}',          [DesignController::class, 'destroy'])->name('design.destroy');

        // Stock Opening
        Route::get   ('stock',                [StockController::class, 'index'])     ->name('stock.index');
        Route::post  ('stock',                [StockController::class, 'store'])     ->name('stock.store');
        Route::put   ('stock/{id}',           [StockController::class, 'update'])    ->name('stock.update');
        Route::delete('stock/{id}',           [StockController::class, 'destroy'])   ->name('stock.destroy');
        Route::get   ('stock/product/{code}', [StockController::class, 'getProduct'])->name('stock.getProduct');

    });

    // ── Transactions ──────────────────────────────────────────────────────────
    Route::prefix('transactions')->name('transactions.')->group(function () {

        // Purchase
        Route::get   ('purchase',                        [PurchaseController::class, 'index'])        ->name('purchase.index');
        Route::get   ('purchase/create',                 [PurchaseController::class, 'create'])       ->name('purchase.create');
        Route::post  ('purchase',                        [PurchaseController::class, 'store'])        ->name('purchase.store');
        Route::get   ('purchase/{brCode}/{invNo}/edit',  [PurchaseController::class, 'edit'])         ->name('purchase.edit');
        Route::put   ('purchase/{brCode}/{invNo}',       [PurchaseController::class, 'update'])       ->name('purchase.update');
        Route::delete('purchase/{brCode}/{invNo}',       [PurchaseController::class, 'destroy'])      ->name('purchase.destroy');
        Route::get   ('purchase/{brCode}/{invNo}/print', [PurchaseController::class, 'printInvoice'])->name('purchase.print');

        // Sale (Type 1 & Type 2 share same controller, saleType from URL)
        Route::prefix('sale')->name('sale.')->group(function () {

            // AJAX helpers
            Route::get('party/{partyCode}', [SaleController::class, 'getParty'])->name('getParty');
            Route::get('order/{ordNo}',     [SaleController::class, 'getOrder'])->name('getOrder');

            // List / Create / Store  — type1 or type2
            Route::get ('type{saleType}',        [SaleController::class, 'index']) ->name('index') ->where('saleType', '[12]');
            Route::get ('type{saleType}/create', [SaleController::class, 'create'])->name('create')->where('saleType', '[12]');
            Route::post('type{saleType}',        [SaleController::class, 'store']) ->name('store') ->where('saleType', '[12]');

            // Edit / Update / Delete / Lock / Print
            Route::get   ('type{saleType}/{brCode}/{invNo}/edit',  [SaleController::class, 'edit'])      ->name('edit');
            Route::put   ('type{saleType}/{brCode}/{invNo}',       [SaleController::class, 'update'])    ->name('update');
            Route::delete('type{saleType}/{brCode}/{invNo}',       [SaleController::class, 'destroy'])   ->name('destroy');
            Route::post  ('type{saleType}/{brCode}/{invNo}/lock',  [SaleController::class, 'toggleLock'])->name('lock');
            Route::get   ('type{saleType}/{brCode}/{invNo}/print', [SaleController::class, 'printBill']) ->name('print');
        });

        // Order / Estimation
        Route::prefix('order')->name('order.')->group(function () {
            Route::get ('type{ordType}',                              [OrderController::class, 'index'])        ->name('index')  ->where('ordType', '[12]');
            Route::get ('type{ordType}/create',                       [OrderController::class, 'create'])       ->name('create') ->where('ordType', '[12]');
            Route::post('type{ordType}',                              [OrderController::class, 'store'])        ->name('store')  ->where('ordType', '[12]');
            Route::get ('type{ordType}/{brCode}/{ordNo}/edit',        [OrderController::class, 'edit'])         ->name('edit');
            Route::put ('type{ordType}/{brCode}/{ordNo}',             [OrderController::class, 'update'])       ->name('update');
            Route::delete('type{ordType}/{brCode}/{ordNo}',           [OrderController::class, 'destroy'])      ->name('destroy');
            Route::post('type{ordType}/{brCode}/{ordNo}/lock',        [OrderController::class, 'lockOrder'])    ->name('lock');
            Route::post('type{ordType}/{brCode}/{ordNo}/convert',     [OrderController::class, 'convertToSale'])->name('convert');
            Route::get ('type{ordType}/{brCode}/{ordNo}/print',       [OrderController::class, 'printEstimation'])->name('print');
        });

        // Sale Return
        Route::prefix('sale-return')->name('sale-return.')->group(function () {
            Route::get  ('customer-sales/{partyCode}',            [SaleReturnController::class, 'getCustomerSales'])->name('customerSales');
            Route::get  ('sale-items/{brCode}/{invNo}/{saleType}',[SaleReturnController::class, 'getSaleItems'])    ->name('saleItems');
            Route::get  ('',                                      [SaleReturnController::class, 'index'])           ->name('index');
            Route::get  ('create',                                [SaleReturnController::class, 'create'])          ->name('create');
            Route::post ('',                                      [SaleReturnController::class, 'store'])           ->name('store');
            Route::get  ('{brCode}/{invNo}/edit',                 [SaleReturnController::class, 'edit'])            ->name('edit');
            Route::put  ('{brCode}/{invNo}',                      [SaleReturnController::class, 'update'])          ->name('update');
            Route::delete('{brCode}/{invNo}',                     [SaleReturnController::class, 'destroy'])         ->name('destroy');
            Route::get  ('{brCode}/{invNo}/print',                [SaleReturnController::class, 'printReturn'])     ->name('print');
        });

        // Purchase Return
        Route::prefix('purchase-return')->name('purchase-return.')->group(function () {
            Route::get  ('supplier-purchases/{partyCode}',  [PurchaseReturnController::class, 'getSupplierPurchases'])->name('supplierPurchases');
            Route::get  ('purchase-items/{brCode}/{invNo}', [PurchaseReturnController::class, 'getPurchaseItems'])    ->name('purchaseItems');
            Route::get  ('',                                [PurchaseReturnController::class, 'index'])               ->name('index');
            Route::get  ('create',                          [PurchaseReturnController::class, 'create'])              ->name('create');
            Route::post ('',                                [PurchaseReturnController::class, 'store'])               ->name('store');
            Route::get  ('{brCode}/{invNo}/edit',           [PurchaseReturnController::class, 'edit'])                ->name('edit');
            Route::put  ('{brCode}/{invNo}',                [PurchaseReturnController::class, 'update'])              ->name('update');
            Route::delete('{brCode}/{invNo}',               [PurchaseReturnController::class, 'destroy'])             ->name('destroy');
            Route::get  ('{brCode}/{invNo}/print',          [PurchaseReturnController::class, 'printReturn'])         ->name('print');
        });

    });

    // ── Reports (future prompts) ──────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
    });

    // ── Utilities ─────────────────────────────────────────────────────────────
    Route::prefix('utilities')->name('utilities.')->group(function () {

        // Change Password — all users
        Route::get ('change-password',  [UserController::class, 'changePasswordForm'])->name('change-password');
        Route::post('change-password',  [UserController::class, 'changePassword'])    ->name('change-password.post');

        // Admin-only utilities
        Route::middleware('admin.check')->group(function () {

            // User Management
            Route::get   ('users',                    [UserController::class, 'index'])         ->name('users.index');
            Route::post  ('users',                    [UserController::class, 'store'])         ->name('users.store');
            Route::put   ('users/{id}',               [UserController::class, 'update'])        ->name('users.update');
            Route::delete('users/{id}',               [UserController::class, 'destroy'])       ->name('users.destroy');
            Route::post  ('users/{id}/reset-password',[UserController::class, 'resetPassword']) ->name('users.reset-password');

            // System Parameters
            Route::get ('system-parameters', [SysParaController::class, 'index'])->name('system-parameters');
            Route::post('system-parameters', [SysParaController::class, 'store'])->name('system-parameters.store');

            // Financial Year
            Route::get   ('financial-year',                          [FinancialYearController::class, 'index'])           ->name('financial-year.index');
            Route::post  ('financial-year',                          [FinancialYearController::class, 'store'])           ->name('financial-year.store');
            Route::post  ('financial-year/{id}/set-active',          [FinancialYearController::class, 'setActive'])       ->name('financial-year.set-active');
            Route::post  ('financial-year/{id}/copy-closing-stock',  [FinancialYearController::class, 'copyClosingStock'])->name('financial-year.copy-ob');
            Route::delete('financial-year/{id}',                     [FinancialYearController::class, 'destroy'])         ->name('financial-year.destroy');

        });
    });

});
