<?php

declare(strict_types=1);

namespace Modules\Notifications\Application\Facades;

use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Modules\Notifications\Application\Jobs\SimulateWebhookCallbackJob;
use Modules\Notifications\Infrastructure\Drivers\DriverInterface;

final readonly class NotificationFacade implements NotificationFacadeInterface
{
    public function __construct(
        private DriverInterface $driver,
    ) {}

    public function notify(NotifyData $data): void
    {
        $success = $this->driver->send(
            toEmail: $data->toEmail,
            subject: $data->subject,
            message: $data->message,
            reference: $data->resourceId->toString(),
        );


        // symulacja callbacku od zewnętrznego dostawcy
        if ($success) {
            $this->triggerDeliveredWebhook($data->resourceId->toString());
        }
    }

    private function triggerDeliveredWebhook(string $reference): void
    {
        // Dispatch job to simulate webhook callback asynchronously
        // In production, this would be a real webhook from external notification provider
        SimulateWebhookCallbackJob::dispatch($reference);
    }
}
