<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDonationRequest;
use App\Http\Requests\UpdateDonationRequest;
use App\Http\Resources\DonationResource;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class DonationController extends Controller
{
    /**
     * Display a listing of donations (only for "manage donations" permission).
     */
    public function index(): AnonymousResourceCollection
    {
        $user = Auth::user();

        if (!$user->can('manage donations') && !$user->is_admin) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to view all donations.');
        }

        $donations = Donation::with(['campaign', 'donor'])->paginate(15);

        return DonationResource::collection($donations);
    }

    /**
     * Store a newly created donation (resource route - only for "manage donations" permission).
     */
    public function store(StoreDonationRequest $request): DonationResource
    {
        $user = Auth::user();

        if (!$user->can('manage donations') && !$user->is_admin) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to create donations.');
        }

        $donation = Donation::create($request->validated());

        activity()
            ->performedOn($donation)
            ->causedBy($user)
            ->log('Donation created');

        return new DonationResource($donation->load(['campaign', 'donor']));
    }

    /**
     * Display the specified donation (only for "manage donations" permission).
     */
    public function show(Donation $donation): DonationResource
    {
        $user = Auth::user();

        if (!$user->can('manage donations') && !$user->is_admin) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to view this donation.');
        }

        return new DonationResource($donation->load(['campaign', 'donor']));
    }

    /**
     * Update the specified donation (only for "manage donations" permission).
     */
    public function update(UpdateDonationRequest $request, Donation $donation): DonationResource
    {
        $user = Auth::user();

        if (!$user->can('manage donations') && !$user->is_admin) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to update this donation.');
        }

        $donation->update($request->validated());

        activity()
            ->performedOn($donation)
            ->causedBy($user)
            ->log('Donation updated');

        return new DonationResource($donation->load(['campaign', 'donor']));
    }

    /**
     * Remove the specified donation (only for "manage donations" permission).
     */
    public function destroy(Donation $donation): Response
    {
        $user = Auth::user();

        if (!$user->can('manage donations') && !$user->is_admin) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to delete this donation.');
        }

        activity()
            ->performedOn($donation)
            ->causedBy($user)
            ->log('Donation deleted');

        $donation->delete();

        return response()->noContent();
    }

    /**
     * Create a donation for a specific campaign.
     */
    public function storeCampaignDonation(StoreDonationRequest $request, Campaign $campaign): DonationResource
    {
        // Check if campaign is approved
        if ($campaign->status !== Campaign::STATUS_APPROVED) {
            abort(Response::HTTP_FORBIDDEN, 'You can only donate to approved campaigns.');
        }

        $donation = Donation::create(array_merge(
            $request->validated(),
            [
                'campaign_id' => $campaign->id,
                'donor_id' => Auth::id(),
            ]
        ));

        activity()
            ->performedOn($donation)
            ->causedBy(Auth::user())
            ->log('Donation made to campaign: ' . $campaign->title);

        return new DonationResource($donation->load(['campaign', 'donor']));
    }

    /**
     * Get donations for a specific campaign.
     */
    public function campaignDonations(Campaign $campaign): AnonymousResourceCollection
    {
        $user = Auth::user();
        $query = $campaign->donations();

        // Users can only see their own donations unless they have "manage donations" permission
        if (!$user->can('manage donations') && !$user->is_admin) {
            $query->where('donor_id', $user->id);
        }

        $donations = $query->with(['donor'])->paginate(15);

        return DonationResource::collection($donations);
    }

    /**
     * Get a specific donation from a campaign.
     */
    public function campaignDonation(Campaign $campaign, Donation $donation): DonationResource
    {
        $user = Auth::user();

        // Check if donation belongs to this campaign
        if ($donation->campaign_id !== $campaign->id) {
            abort(Response::HTTP_NOT_FOUND, 'Donation not found for this campaign.');
        }

        // Users can only see their own donations unless they have "manage donations" permission
        if (!$user->can('manage donations') && !$user->is_admin && $donation->donor_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'You can only view your own donations.');
        }

        return new DonationResource($donation->load(['campaign', 'donor']));
    }
}
