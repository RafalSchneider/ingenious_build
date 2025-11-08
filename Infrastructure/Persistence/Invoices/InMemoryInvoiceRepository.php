<?php

namespace Infrastructure\Persistence\Invoices;

use Domain\Invoices\Entities\Invoice;
use Domain\Invoices\Repositories\InvoiceRepositoryInterface;

class InMemoryInvoiceRepository implements InvoiceRepositoryInterface
{
    /** @var Invoice[] */
    private array $invoices = [];
    private int $nextId = 1;

    public function findById(int $id): ?Invoice
    {
        foreach ($this->invoices as $invoice) {
            if ($invoice->getId() === $id) {
                return $invoice;
            }
        }
        return null;
    }

    public function save(Invoice $invoice): void
    {
        if ($invoice->getId() === 0) {
            // Nadaj nowe ID
            $reflection = new \ReflectionClass($invoice);
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($invoice, $this->nextId++);
        }
        // Nadpisz lub dodaj fakturę
        $this->invoices[$invoice->getId()] = $invoice;
    }
}
