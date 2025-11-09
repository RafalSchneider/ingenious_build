<?php

declare(strict_types=1);

namespace Modules\Notifications\Infrastructure\Drivers;

class DummyDriver implements DriverInterface
{
    public function send(
        string $toEmail,
        string $subject,
        string $message,
        string $reference,
    ): bool {
        // Dummy notification provider - symuluje wysĹ‚anie emaila
        // Webhook jest wywoĹ‚any przez NotificationFacade

        return true;
    }
}
