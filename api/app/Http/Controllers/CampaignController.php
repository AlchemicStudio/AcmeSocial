<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    /**
     * Display a listing of campaigns.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = Auth::user();
        $query = Campaign::query();

        // Base query: show approved campaigns for regular users
        if (!$user->can('manage campaigns') && !$user->is_admin) {
            $query->where('status', Campaign::STATUS_APPROVED);
        } else {
            // Users with "manage campaigns" permission or campaign authors can see all statuses
            if (!$user->can('manage campaigns') && !$user->is_admin) {
                $query->where(function ($q) use ($user) {
                    $q->where('status', Campaign::STATUS_APPROVED)
                      ->orWhere('creator_id', $user->id);
                });
            }
        }

        // Add filtering and searching
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $campaigns = $query->with('creator')->paginate(15);

        return CampaignResource::collection($campaigns);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(StoreCampaignRequest $request): CampaignResource
    {
        $campaign = Campaign::create(array_merge(
            $request->validated(),
            ['creator_id' => Auth::id()]
        ));

        activity()
            ->performedOn($campaign)
            ->causedBy(Auth::user())
            ->log('Campaign created');

        return new CampaignResource($campaign->load('creator'));
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign): CampaignResource
    {
        $user = Auth::user();

        // Check if user can view this campaign
        if ($campaign->status !== Campaign::STATUS_APPROVED) {
            if (!$user->can('manage campaigns') &&
                !$user->is_admin &&
                $campaign->creator_id !== $user->id) {
                abort(Response::HTTP_FORBIDDEN, 'You cannot view this campaign.');
            }
        }

        return new CampaignResource($campaign->load('creator'));
    }

    /**
     * Update the specified campaign.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign): CampaignResource
    {
        $user = Auth::user();

        // Check authorization
        if (!$user->can('manage campaigns') && !$user->is_admin) {
            // Authors can only update unapproved campaigns
            if ($campaign->creator_id !== $user->id ||
                $campaign->status === Campaign::STATUS_APPROVED) {
                abort(Response::HTTP_FORBIDDEN, 'You cannot update this campaign.');
            }
        }

        $campaign->update($request->validated());

        activity()
            ->performedOn($campaign)
            ->causedBy(Auth::user())
            ->log('Campaign updated');

        return new CampaignResource($campaign->load('creator'));
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(Campaign $campaign): Response
    {
        $user = Auth::user();

        // Check authorization
        if (!$user->can('manage campaigns') && !$user->is_admin) {
            // Authors can only delete unapproved campaigns
            if ($campaign->creator_id !== $user->id ||
                $campaign->status === Campaign::STATUS_APPROVED) {
                abort(Response::HTTP_FORBIDDEN, 'You cannot delete this campaign.');
            }
        }

        activity()
            ->performedOn($campaign)
            ->causedBy(Auth::user())
            ->log('Campaign deleted');

        $campaign->delete();

        return response()->noContent();
    }

    /**
     * Get campaign statistics.
     */
    public function statistics(Campaign $campaign): array
    {
        $user = Auth::user();

        if (!$user->can('manage campaigns') && !$user->is_admin) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to view campaign statistics.');
        }

        return [
            'total_donations' => $campaign->donations()->count(),
            'total_amount' => $campaign->donations()->where('status', \App\Models\Donation::STATUS_COMPLETED)->sum('amount'),
            'unique_donors' => $campaign->donations()->distinct('donor_id')->count('donor_id'),
            'average_donation' => $campaign->donations()->where('status', \App\Models\Donation::STATUS_COMPLETED)->avg('amount') ?? 0,
            'completion_percentage' => $campaign->goal_amount > 0 ? ($campaign->current_amount / $campaign->goal_amount) * 100 : 0,
            'statistics' => $campaign->getStatistics()
        ];
    }

    /**
     * Approve a campaign.
     */
    public function approve(Campaign $campaign): CampaignResource
    {
        $user = Auth::user();

        if (!$user->can('manage campaigns') && !$user->is_admin) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to approve campaigns.');
        }

        $campaign->update([
            'status' => Campaign::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        activity()
            ->performedOn($campaign)
            ->causedBy($user)
            ->log('Campaign approved');

        return new CampaignResource($campaign->load('creator', 'approver'));
    }

    /**
     * Reject a campaign.
     */
    public function reject(Request $request, Campaign $campaign): CampaignResource
    {
        $user = Auth::user();

        if (!$user->can('manage campaigns') && !$user->is_admin) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to reject campaigns.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $campaign->update([
            'status' => Campaign::STATUS_REJECTED,
            'rejected_by' => $user->id,
            'rejected_reason' => $request->get('reason'),
        ]);

        activity()
            ->performedOn($campaign)
            ->causedBy($user)
            ->log('Campaign rejected');

        return new CampaignResource($campaign->load('creator', 'rejector'));
    }
}
