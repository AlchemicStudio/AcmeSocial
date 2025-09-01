<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;

test('user attributes are cast correctly', function () {
    $user = User::factory()->create([
        'email_verified_at' => '2024-01-15 10:00:00',
        'password' => 'password123',
        'is_admin' => '1',
    ]);

    expect($user->email_verified_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($user->password)->toBeString() // Password is hashed
        ->and($user->is_admin)->toBeInt();
});

test('user fillable attributes are correct', function () {
    $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    $user = new User();
    expect($user->getFillable())->toBe($fillable);
});

test('user hidden attributes are correct', function () {
    $hidden = [
        'password',
        'remember_token',
    ];

    $user = new User();
    expect($user->getHidden())->toBe($hidden);
});

test('user uses correct traits', function () {
    $user = new User();

    expect($user)->toUse([
        Illuminate\Database\Eloquent\Factories\HasFactory::class,
        Illuminate\Notifications\Notifiable::class,
        Illuminate\Database\Eloquent\Concerns\HasUuids::class,
        Spatie\Activitylog\Traits\LogsActivity::class,
        Spatie\Permission\Traits\HasRoles::class,
    ]);
});

test('user extends Authenticatable', function () {
    $user = new User();

    expect($user)->toBeInstanceOf(Illuminate\Foundation\Auth\User::class);
});

test('user can be created with all required attributes', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'is_admin' => 0,
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com')
        ->and($user->is_admin)->toBe(0);
});

test('user can be an admin', function () {
    $adminUser = User::factory()->create(['is_admin' => 1]);
    $regularUser = User::factory()->create(['is_admin' => 0]);

    expect($adminUser->is_admin)->toBe(1)
        ->and($regularUser->is_admin)->toBe(0);
});

test('user password is hashed when created', function () {
    $plainPassword = 'password123';
    $user = User::factory()->create(['password' => $plainPassword]);

    expect($user->password)->not->toBe($plainPassword)
        ->and(Hash::check($plainPassword, $user->password))->toBeTrue();
});

test('user can create campaigns', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['creator_id' => $user->id]);

    expect($campaign->creator_id)->toBe($user->id)
        ->and($campaign->creator)->toBeInstanceOf(User::class)
        ->and($campaign->creator->id)->toBe($user->id);
});

test('user can approve campaigns', function () {
    $approver = User::factory()->create();
    $campaign = Campaign::factory()->create(['approved_by' => $approver->id]);

    expect($campaign->approved_by)->toBe($approver->id)
        ->and($campaign->approver)->toBeInstanceOf(User::class)
        ->and($campaign->approver->id)->toBe($approver->id);
});

test('user can reject campaigns', function () {
    $rejector = User::factory()->create();
    $campaign = Campaign::factory()->create(['rejected_by' => $rejector->id]);

    expect($campaign->rejected_by)->toBe($rejector->id)
        ->and($campaign->rejector)->toBeInstanceOf(User::class)
        ->and($campaign->rejector->id)->toBe($rejector->id);
});

test('user can make donations', function () {
    $donor = User::factory()->create();
    $donation = Donation::factory()->create(['donor_id' => $donor->id]);

    expect($donation->donor_id)->toBe($donor->id)
        ->and($donation->donor)->toBeInstanceOf(User::class)
        ->and($donation->donor->id)->toBe($donor->id);
});

test('user has activity log options configured', function () {
    $user = new User();
    $logOptions = $user->getActivitylogOptions();

    expect($logOptions)->toBeInstanceOf(Spatie\Activitylog\LogOptions::class);
});

test('user activity log excludes sensitive fields', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'password' => 'newpassword',
    ]);

    // Update user to trigger activity log
    $user->update([
        'name' => 'Updated Name',
        'password' => 'updatedpassword',
    ]);

    $lastActivity = $user->activities()->latest()->first();

    expect($lastActivity)->not->toBeNull()
        ->and($lastActivity->properties->get('attributes'))->not->toHaveKey('password')
        ->and($lastActivity->properties->get('old'))->not->toHaveKey('password');
});

test('user can have roles using Spatie permissions', function () {
    $user = User::factory()->create();

    expect($user)->toHaveMethod('assignRole')
        ->and($user)->toHaveMethod('hasRole')
        ->and($user)->toHaveMethod('getRoleNames');
});

test('user factory creates valid users', function () {
    $users = User::factory(3)->create();

    expect($users)->toHaveCount(3);

    foreach ($users as $user) {
        expect($user->name)->toBeString()->not->toBeEmpty()
            ->and($user->email)->toBeString()->toContain('@')
            ->and($user->password)->toBeString()->not->toBeEmpty();
    }
});
