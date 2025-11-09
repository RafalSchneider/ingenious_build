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
        return true;
    }
}
