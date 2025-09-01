<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DonationReceiptFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DonationReceipt extends Model implements HasMedia
{
    /** @use HasFactory<DonationReceiptFactory> */
    use HasFactory, HasUuids, LogsActivity, InteractsWithMedia;

    public const RECEIPT_MEDIA_COLLECTION = 'receipt';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'donation_id',
        'receipt_number',
        'issued_date',
        'email_sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'email_sent_at' => 'date',
        ];
    }

    /**
     * Donation relationship.
     *
     * @return BelongsTo<Donation, DonationReceipt>
     */
    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    protected static function newFactory(): DonationReceiptFactory
    {
        return DonationReceiptFactory::new();
    }
}
