<?php

declare(strict_types=1);

namespace Modules\Notifications\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class TriggerDeliveredWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $reference,
    ) {}

    /**
     * Execute the job.
     * Wywołuje webhook informujący o dostarczeniu emaila
     */
    public function handle(): void
    {
        $webhookUrl = route('notification.hook', [
            'action' => 'delivered',
            'reference' => $this->reference
        ]);

        Http::get($webhookUrl);
    }
}
