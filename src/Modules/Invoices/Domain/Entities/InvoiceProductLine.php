<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

class InvoiceProductLine
{
    private string $productName;
    private int $quantity;
    private int $unitPrice;

    public function __construct(string $productName, int $quantity, int $unitPrice)
    {
        $this->productName = $productName;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }
    public function getQuantity(): int
    {
        return $this->quantity;
    }
    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }
    public function getTotalUnitPrice(): int
    {
        return $this->quantity * $this->unitPrice;
    }
}
