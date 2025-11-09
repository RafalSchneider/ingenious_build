<?php

declare(strict_types=1);

namespace Modules\Notifications\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

/**
 * Simulates external webhook callback from notification provider
 * In production, this would be a real HTTP callback from external service
 */
class SimulateWebhookCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $reference,
    ) {}

    public function handle(): void
    {
        $webhookUrl = route('notification.hook', [
            'action' => 'delivered',
            'reference' => $this->reference
        ]);

        Http::get($webhookUrl);
    }
}
