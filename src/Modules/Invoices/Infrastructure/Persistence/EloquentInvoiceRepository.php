<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use App\Models\Invoice as EloquentInvoice;
use App\Models\InvoiceProductLine as EloquentInvoiceProductLine;
use Ramsey\Uuid\Uuid;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function findById(string $id): ?Invoice
    {
        $eloquentInvoice = EloquentInvoice::with('productLines')->find($id);
        if (!$eloquentInvoice) {
            return null;
        }

        return $this->toDomainEntity($eloquentInvoice);
    }

    public function save(Invoice $invoice): void
    {
        $invoiceId = $invoice->getId();

        if ($invoiceId) {
            $eloquentInvoice = EloquentInvoice::find($invoiceId);
            if (!$eloquentInvoice) {
                throw new \RuntimeException("Invoice with ID {$invoiceId} not found");
            }
        } else {
            $eloquentInvoice = new EloquentInvoice();
            $eloquentInvoice->id = Uuid::uuid4()->toString();
            $invoice->setId($eloquentInvoice->id);
        }

        $eloquentInvoice->customer_name = $invoice->getCustomerName();
        $eloquentInvoice->customer_email = $invoice->getCustomerEmail();
        $eloquentInvoice->status = $invoice->getStatus();
        $eloquentInvoice->save();

        // Usunięcie istniejących linii produktów i dodanie nowych
        $eloquentInvoice->productLines()->delete();

        foreach ($invoice->getProductLines() as $productLine) {
            $eloquentProductLine = new EloquentInvoiceProductLine();
            $eloquentProductLine->id = Uuid::uuid4()->toString();
            $eloquentProductLine->invoice_id = $eloquentInvoice->id;
            $eloquentProductLine->name = $productLine->getProductName();
            $eloquentProductLine->price = $productLine->getUnitPrice();
            $eloquentProductLine->quantity = $productLine->getQuantity();
            $eloquentProductLine->save();
        }
    }

    private function toDomainEntity(EloquentInvoice $eloquentInvoice): Invoice
    {
        $productLines = [];
        foreach ($eloquentInvoice->productLines as $eloquentProductLine) {
            $productLines[] = new InvoiceProductLine(
                $eloquentProductLine->name,
                $eloquentProductLine->quantity,
                $eloquentProductLine->price
            );
        }

        return new Invoice(
            $eloquentInvoice->id,
            $eloquentInvoice->status,
            $eloquentInvoice->customer_name,
            $eloquentInvoice->customer_email,
            $productLines
        );
    }
}
