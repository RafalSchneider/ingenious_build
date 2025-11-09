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
        // Dummy notification provider - symuluje wysłanie emaila
        // W prawdziwej implementacji (SendGrid, Mailgun, etc.) 
        // dostawca wysyłałby email i wywołał webhook gdy email zostanie dostarczony

        // Tutaj nic nie robimy - webhook musi być wywołany zewnętrznie
        // Aby zasymulować dostarczenie, wywołaj ręcznie:
        // GET /notification/hook/delivered/{reference}

        return true;
    }
}
