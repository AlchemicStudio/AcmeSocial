<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DonationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory, HasUuids, LogsActivity;

    public const VISIBILITY_PUBLIC = 0;
    public const VISIBILITY_ANONYMOUS = 1;

    public const STATUS_PENDING = 0;
    public const STATUS_COMPLETED = 1;
    public const STATUS_FAILED = 2;
    public const STATUS_REFUNDED = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'campaign_id',
        'donor_id',
        'amount',
        'currency',
        'message',
        'visibility',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'visibility' => 'integer',
            'status' => 'integer',
        ];
    }

    /**
     * Campaign relationship.
     *
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Donor relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    protected static function newFactory(): DonationFactory
    {
        return DonationFactory::new();
    }
}
