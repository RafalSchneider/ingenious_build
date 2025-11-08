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
        $eloquentInvoice = $invoice->getId()
            ? EloquentInvoice::find($invoice->getId())
            : new EloquentInvoice();

        if (!$eloquentInvoice->exists) {
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
            $eloquentProductLine->product_name = $productLine->getProductName();
            $eloquentProductLine->quantity = $productLine->getQuantity();
            $eloquentProductLine->unit_price = $productLine->getUnitPrice();
            $eloquentProductLine->total_unit_price = $productLine->getTotalUnitPrice();
            $eloquentProductLine->save();
        }
    }

    private function toDomainEntity(EloquentInvoice $eloquentInvoice): Invoice
    {
        $productLines = [];
        foreach ($eloquentInvoice->productLines as $eloquentProductLine) {
            $productLines[] = new InvoiceProductLine(
                $eloquentProductLine->product_name,
                $eloquentProductLine->quantity,
                $eloquentProductLine->unit_price
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
