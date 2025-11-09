<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence;

use App\Models\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function findById(string $id): ?Invoice
    {
        return Invoice::with('productLines')->find($id);
    }

    public function save(Invoice $invoice): void
    {
        $invoice->save();
    }
}
