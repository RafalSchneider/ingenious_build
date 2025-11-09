<?php

declare(strict_types=1);

namespace Modules\Notifications\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $toEmail,
        public readonly string $subject,
        public readonly string $message,
        public readonly string $reference,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::raw($this->message, function ($mail) {
            $mail->to($this->toEmail)
                ->subject($this->subject);
        });

        // Po wysłaniu emaila, uruchom webhook w kolejce
        TriggerDeliveredWebhookJob::dispatch($this->reference)
            ->delay(now()->addSeconds(2)); // Symulacja opóźnienia dostarczenia
    }
}
