<?php

namespace Infrastructure\Database\Repositories;

use Domain\Invoices\Entities\Invoice;
use Domain\Invoices\Repositories\InvoiceRepositoryInterface;
use Infrastructure\Database\Eloquent\Invoice as EloquentInvoice;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function findById(int $id): ?Invoice
    {
        $eloquentInvoice = EloquentInvoice::with('productLines')->find($id);
        if (!$eloquentInvoice) {
            return null;
        }
        // Mapowanie z Eloquent na encję domenową
        // ...
        return null; // TODO: implement mapping
    }

    public function save(Invoice $invoice): void
    {
        // Mapowanie z encji domenowej na Eloquent
        // ...
        // TODO: implement mapping and save
    }
}
