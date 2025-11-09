<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Invoices\Domain\Enums\StatusEnum;

class Invoice extends Model
{
    protected $table = 'invoices';

    // UUID configuration
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'status',
        'customer_name',
        'customer_email',
    ];

    protected $casts = [
        'status' => StatusEnum::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Invoice $invoice) {
            if (empty($invoice->id)) {
                $invoice->id = (string) Str::uuid();
            }
        });
    }

    public function productLines(): HasMany
    {
        return $this->hasMany(InvoiceProductLine::class);
    }

    // ==================== DOMAIN LOGIC ====================
    // To jest DDD - logika biznesowa w modelu!

    public function getTotalPrice(): int
    {
        return $this->productLines->sum(fn($line) => $line->getTotalUnitPrice());
    }

    public function canBeSent(): bool
    {
        if ($this->status !== StatusEnum::Draft) {
            return false;
        }

        if ($this->productLines->isEmpty()) {
            return false;
        }

        foreach ($this->productLines as $line) {
            if ($line->quantity <= 0 || $line->price <= 0) {
                return false;
            }
        }

        return true;
    }

    public function markAsSending(): void
    {
        if ($this->status === StatusEnum::Draft && $this->canBeSent()) {
            $this->status = StatusEnum::Sending;
            $this->save();
        }
    }

    public function markAsSentToClient(): void
    {
        if ($this->status === StatusEnum::Sending) {
            $this->status = StatusEnum::SentToClient;
            $this->save();
        }
    }
}
