<?php

namespace Domain\Invoices\Repositories;

use Domain\Invoices\Entities\Invoice;

interface InvoiceRepositoryInterface
{
    public function findById(int $id): ?Invoice;
    public function save(Invoice $invoice): void;
    // Add other methods as needed
}
