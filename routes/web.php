<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\DeliveryNoteController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockMovementController;
use App\Http\Middleware\RequireWarehousePassword;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AccessController::class, 'login'])->name('access.login');
Route::post('/login', [AccessController::class, 'unlock'])->name('access.unlock');
Route::post('/logout', [AccessController::class, 'logout'])->name('access.logout');

Route::middleware(RequireWarehousePassword::class)->group(function (): void {
    Route::get('/', [InventoryController::class, 'dashboard'])->name('dashboard');
    Route::resource('products', ProductController::class)->except(['show', 'destroy']);
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::resource('movements', StockMovementController::class)->only(['index', 'create', 'store']);

    Route::get('delivery-notes/{deliveryNote}/download', [DeliveryNoteController::class, 'download'])->name('delivery-notes.download');
    Route::resource('delivery-notes', DeliveryNoteController::class)->parameters(['delivery-notes' => 'deliveryNote'])->only(['index', 'store', 'show']);
});
