<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Listeners;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;

/**
 * Listener that handles invoice status update when notification is delivered
 */
final readonly class InvoiceDeliveredListener
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    /**
     * Handle the ResourceDeliveredEvent
     * Updates invoice status from 'sending' to 'sent-to-client'
     */
    public function handle(ResourceDeliveredEvent $event): void
    {
        $invoiceId = $event->resourceId->toString();

        $invoice = $this->invoiceRepository->findById($invoiceId);

        if ($invoice && $invoice->status === StatusEnum::Sending) {
            $invoice->markAsSentToClient();
        }
    }
}
