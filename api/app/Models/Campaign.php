<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;

class Campaign extends Model implements HasMedia
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, HasUuids, HasTags, LogsActivity, SoftDeletes, InteractsWithMedia;

    public const STATUS_DRAFT = 0;
    public const STATUS_PENDING = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;
    public const STATUS_COMPLETED = 4;
    public const STATUS_CANCELLED = 5;

    public const LOGO_MEDIA_COLLECTION = 'logo';
    public const OTHER_MEDIA_COLLECTION = 'medias';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'goal_amount',
        'current_amount',
        'start_date',
        'end_date',
        'status',
        'creator_id',
        'approved_at',
        'approved_by',
        'rejected_by',
        'rejected_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'goal_amount' => 'integer',
            'current_amount' => 'integer',
            'status' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Campaign creator relationship.
     *
     * @return BelongsTo<User, Campaign>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Campaign approver relationship.
     *
     * @return BelongsTo<User, Campaign>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Campaign rejector relationship.
     *
     * @return BelongsTo<User, Campaign>
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Campaign donations relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Donation>
     */
    public function donations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    /**
     * Get statistics for completed donations in this campaign.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $completedDonations = $this->donations()
            ->where('status', Donation::STATUS_COMPLETED)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as quantity, SUM(amount) as amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        if ($completedDonations->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Daily Quantity',
                        'data' => []
                    ],
                    [
                        'label' => 'Daily Amount',
                        'data' => []
                    ]
                ]
            ];
        }

        $labels = [];
        $quantities = [];
        $amounts = [];

        foreach ($completedDonations as $donation) {
            $labels[] = $donation->date;
            $quantities[] = (int) $donation->quantity;
            $amounts[] = (int) $donation->amount;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Daily Quantity',
                    'data' => $quantities
                ],
                [
                    'label' => 'Daily Amount',
                    'data' => $amounts
                ]
            ]
        ];
    }

    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }
}
