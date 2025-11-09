<?php

declare(strict_types=1);

namespace Modules\Notifications\Infrastructure\Drivers;

use Modules\Notifications\Application\Jobs\SendInvoiceEmailJob;

class LaravelMailDriver implements DriverInterface
{
    public function send(
        string $toEmail,
        string $subject,
        string $message,
        string $reference,
    ): bool {
        // Wysyłanie emaila asynchronicznie przez kolejkę
        SendInvoiceEmailJob::dispatch(
            toEmail: $toEmail,
            subject: $subject,
            message: $message,
            reference: $reference,
        );

        return true;
    }
}
