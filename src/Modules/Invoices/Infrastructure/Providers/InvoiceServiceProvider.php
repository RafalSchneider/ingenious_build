<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Invoices\Infrastructure\Persistence\EloquentInvoiceRepository;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Modules\Invoices\Application\Listeners\InvoiceDeliveredListener;

class InvoiceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            InvoiceRepositoryInterface::class,
            EloquentInvoiceRepository::class
        );
    }

    public function boot(): void
    {
        Event::listen(
            ResourceDeliveredEvent::class,
            InvoiceDeliveredListener::class
        );
    }
}
