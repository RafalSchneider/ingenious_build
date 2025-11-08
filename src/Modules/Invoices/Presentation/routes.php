<?php

declare(strict_types=1);

use Modules\Invoices\Presentation\Http\Controllers\InvoiceController;

Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
Route::post('/invoices', [InvoiceController::class, 'store']);
Route::post('/invoices/{id}/send', [InvoiceController::class, 'send']);
