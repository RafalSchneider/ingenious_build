<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Repositories;

use Modules\Invoices\Domain\Entities\Invoice;

interface InvoiceRepositoryInterface
{
    public function findById(string $id): ?Invoice;
    public function save(Invoice $invoice): void;
}
