<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['creator_id' => $user->id]);

        $this->assertInstanceOf(User::class, $campaign->creator);
        $this->assertEquals($user->id, $campaign->creator->id);
    }

    public function test_campaign_belongs_to_approver(): void
    {
        $approver = User::factory()->create();
        $campaign = Campaign::factory()->create(['approved_by' => $approver->id]);

        $this->assertInstanceOf(User::class, $campaign->approver);
        $this->assertEquals($approver->id, $campaign->approver->id);
    }

test('campaign belongs to rejector', function () {
    $rejector = User::factory()->create();
    $campaign = Campaign::factory()->create(['rejected_by' => $rejector->id]);

    expect($campaign->rejector)->toBeInstanceOf(User::class)
        ->and($campaign->rejector->id)->toBe($rejector->id);
});

test('campaign has many donations', function () {
    $campaign = Campaign::factory()->create();
    $donation1 = Donation::factory()->create(['campaign_id' => $campaign->id]);
    $donation2 = Donation::factory()->create(['campaign_id' => $campaign->id]);

    expect($campaign->donations)->toHaveCount(2)
        ->and($campaign->donations->first())->toBeInstanceOf(Donation::class)
        ->and($campaign->donations->pluck('id'))->toContain($donation1->id, $donation2->id);
});

test('campaign status constants are defined', function () {
    expect(Campaign::STATUS_DRAFT)->toBe(0)
        ->and(Campaign::STATUS_PENDING)->toBe(1)
        ->and(Campaign::STATUS_APPROVED)->toBe(2)
        ->and(Campaign::STATUS_REJECTED)->toBe(3)
        ->and(Campaign::STATUS_COMPLETED)->toBe(4)
        ->and(Campaign::STATUS_CANCELLED)->toBe(5);
});

test('campaign media collection constants are defined', function () {
    expect(Campaign::LOGO_MEDIA_COLLECTION)->toBe('logo')
        ->and(Campaign::OTHER_MEDIA_COLLECTION)->toBe('medias');
});

test('campaign attributes are cast correctly', function () {
    $campaign = Campaign::factory()->create([
        'goal_amount' => '1000',
        'current_amount' => '500',
        'status' => '2',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'approved_at' => '2024-01-15 10:00:00',
    ]);

    expect($campaign->goal_amount)->toBeInt()
        ->and($campaign->current_amount)->toBeInt()
        ->and($campaign->status)->toBeInt()
        ->and($campaign->start_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($campaign->end_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($campaign->approved_at)->toBeInstanceOf(Carbon\Carbon::class);
});

test('campaign fillable attributes are correct', function () {
    $fillable = [
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

    $campaign = new Campaign();
    expect($campaign->getFillable())->toBe($fillable);
});

test('get statistics returns empty data when no donations', function () {
    $campaign = Campaign::factory()->create();

    $statistics = $campaign->getStatistics();

    expect($statistics)->toBe([
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
    ]);
});

test('get statistics returns data for completed donations', function () {
    $campaign = Campaign::factory()->create();

    // Create completed donations on different dates
    Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => Donation::STATUS_COMPLETED,
        'amount' => 100,
        'created_at' => '2024-01-01 10:00:00',
    ]);

    Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => Donation::STATUS_COMPLETED,
        'amount' => 200,
        'created_at' => '2024-01-01 15:00:00',
    ]);

    Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => Donation::STATUS_COMPLETED,
        'amount' => 150,
        'created_at' => '2024-01-02 10:00:00',
    ]);

    // Create pending donation (should not be included)
    Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => Donation::STATUS_PENDING,
        'amount' => 300,
        'created_at' => '2024-01-01 12:00:00',
    ]);

    $statistics = $campaign->getStatistics();

    expect($statistics['labels'])->toHaveCount(2)
        ->and($statistics['datasets'][0]['label'])->toBe('Daily Quantity')
        ->and($statistics['datasets'][1]['label'])->toBe('Daily Amount')
        ->and($statistics['datasets'][0]['data'])->toBe([2, 1]) // 2 donations on 2024-01-01, 1 on 2024-01-02
        ->and($statistics['datasets'][1]['data'])->toBe([300, 150]); // 300 total on 2024-01-01, 150 on 2024-01-02
});

test('campaign uses correct traits', function () {
    $campaign = new Campaign();

    expect($campaign)->toUse([
        Illuminate\Database\Eloquent\Factories\HasFactory::class,
        Illuminate\Database\Eloquent\Concerns\HasUuids::class,
        Spatie\Tags\HasTags::class,
        Spatie\Activitylog\Traits\LogsActivity::class,
        Illuminate\Database\Eloquent\SoftDeletes::class,
        Spatie\MediaLibrary\InteractsWithMedia::class,
    ]);
});

test('campaign implements HasMedia interface', function () {
    $campaign = new Campaign();

    expect($campaign)->toBeInstanceOf(Spatie\MediaLibrary\HasMedia::class);
});
