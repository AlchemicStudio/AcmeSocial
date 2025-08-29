<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;
use Spatie\Tags\HasTags;

class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, HasUuid, HasTags, LogsActivity, SoftDeletes;

    public const STATUS_DRAFT = 0;
    public const STATUS_PENDING = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;
    public const STATUS_COMPLETED = 4;
    public const STATUS_CANCELLED = 5;

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
        'cover_image_url',
        'video_url',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }
}
