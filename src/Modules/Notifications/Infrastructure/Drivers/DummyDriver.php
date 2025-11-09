<?php

declare(strict_types=1);

namespace Modules\Notifications\Infrastructure\Drivers;

use Illuminate\Support\Facades\Http;

class DummyDriver implements DriverInterface
{
    public function send(
        string $toEmail,
        string $subject,
        string $message,
        string $reference,
    ): bool {
        // Dummy driver - symuluje wysłanie emaila
        // W prawdziwej implementacji tutaj byłoby wywołanie API (SendGrid, Mailgun, etc.)

        // Symuluj callback od dostawcy emaili - wywołaj webhook
        $webhookUrl = route('notification.hook', [
            'action' => 'delivered',
            'reference' => $reference
        ]);

        Http::get($webhookUrl);

        return true;
    }
}
