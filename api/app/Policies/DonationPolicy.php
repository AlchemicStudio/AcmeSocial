<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DonationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view donations') || $user->is_admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Donation $donation): bool
    {
        // Admins can view all donations
        if ($user->hasPermissionTo('view donations') || $user->is_admin) {
            return true;
        }

        // Donors can view their own donations
        if ($user->id === $donation->donor_id) {
            return true;
        }

        // Public donations can be viewed by authenticated users
        return $donation->visibility === Donation::VISIBILITY_PUBLIC;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create donations') || !$user->is_admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Donation $donation): bool
    {
        // Admins can update all donations
        if ($user->hasPermissionTo('manage donations') || $user->is_admin) {
            return true;
        }

        // Donors can update their own donations if still pending
        return $user->id === $donation->donor_id &&
               $donation->status === Donation::STATUS_PENDING;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Donation $donation): bool
    {
        // Only admins can delete donations
        return $user->hasPermissionTo('manage donations') || $user->is_admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Donation $donation): bool
    {
        return $user->hasPermissionTo('manage donations') || $user->is_admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Donation $donation): bool
    {
        return $user->hasPermissionTo('manage donations') || $user->is_admin;
    }
}
