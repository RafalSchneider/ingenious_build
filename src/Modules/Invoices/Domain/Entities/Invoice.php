<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

class Invoice
{
    private ?string $id;
    private string $status;
    private string $customerName;
    private string $customerEmail;
    /** @var InvoiceProductLine[] */
    private array $productLines = [];

    public function __construct(
        ?string $id,
        string $status,
        string $customerName,
        string $customerEmail,
        array $productLines = []
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->productLines = $productLines;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
    public function setId(string $id): void
    {
        $this->id = $id;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getCustomerName(): string
    {
        return $this->customerName;
    }
    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }
    public function getProductLines(): array
    {
        return $this->productLines;
    }

    public function getTotalPrice(): int
    {
        return array_sum(array_map(fn($line) => $line->getTotalUnitPrice(), $this->productLines));
    }

    public function canBeSent(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }
        if (empty($this->productLines)) {
            return false;
        }
        foreach ($this->productLines as $line) {
            if ($line->getQuantity() <= 0 || $line->getUnitPrice() <= 0) {
                return false;
            }
        }
        return true;
    }

    public function markAsSending(): void
    {
        if ($this->status === 'draft' && $this->canBeSent()) {
            $this->status = 'sending';
        }
    }

    public function markAsSentToClient(): void
    {
        if ($this->status === 'sending') {
            $this->status = 'sent-to-client';
        }
    }
}
