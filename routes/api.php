<?php

declare(strict_types=1);

require __DIR__.'/../src/Modules/Notifications/Presentation/routes.php';

use Infrastructure\Http\Controllers\InvoiceController;

Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
Route::post('/invoices', [InvoiceController::class, 'store']);
Route::post('/invoices/{id}/send', [InvoiceController::class, 'send']);
