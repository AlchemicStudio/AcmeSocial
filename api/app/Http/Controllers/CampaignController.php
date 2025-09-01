<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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

    /**
     * Upload a logo for the campaign.
     */
    public function uploadLogo(Request $request, Campaign $campaign): JsonResponse
    {
        $this->checkCampaignMediaPermission($campaign);

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,gif,webp|max:5120', // 5MB max
        ]);

        // Clear existing logo first
        $campaign->clearMediaCollection(Campaign::LOGO_MEDIA_COLLECTION);

        // Add new logo
        $media = $campaign->addMediaFromRequest('logo')
            ->toMediaCollection(Campaign::LOGO_MEDIA_COLLECTION);

        activity()
            ->performedOn($campaign)
            ->causedBy(Auth::user())
            ->log('Campaign logo uploaded');

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'media' => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
                'collection_name' => $media->collection_name,
            ]
        ]);
    }

    /**
     * Get the campaign logo.
     */
    public function getLogo(Campaign $campaign): JsonResponse
    {
        $this->checkCampaignViewPermission($campaign);

        $logo = $campaign->getFirstMedia(Campaign::LOGO_MEDIA_COLLECTION);

        if (!$logo) {
            return response()->json([
                'message' => 'No logo found',
                'media' => null
            ], 404);
        }

        return response()->json([
            'media' => [
                'id' => $logo->id,
                'name' => $logo->name,
                'file_name' => $logo->file_name,
                'mime_type' => $logo->mime_type,
                'size' => $logo->size,
                'url' => $logo->getUrl(),
                'collection_name' => $logo->collection_name,
            ]
        ]);
    }

    /**
     * Update the campaign logo.
     */
    public function updateLogo(Request $request, Campaign $campaign): JsonResponse
    {
        $this->checkCampaignMediaPermission($campaign);

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,gif,webp|max:5120', // 5MB max
        ]);

        // Clear existing logo first
        $campaign->clearMediaCollection(Campaign::LOGO_MEDIA_COLLECTION);

        // Add new logo
        $media = $campaign->addMediaFromRequest('logo')
            ->toMediaCollection(Campaign::LOGO_MEDIA_COLLECTION);

        activity()
            ->performedOn($campaign)
            ->causedBy(Auth::user())
            ->log('Campaign logo updated');

        return response()->json([
            'message' => 'Logo updated successfully',
            'media' => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
                'collection_name' => $media->collection_name,
            ]
        ]);
    }

    /**
     * Delete the campaign logo.
     */
    public function deleteLogo(Campaign $campaign): JsonResponse
    {
        $this->checkCampaignMediaPermission($campaign);

        $logo = $campaign->getFirstMedia(Campaign::LOGO_MEDIA_COLLECTION);

        if (!$logo) {
            return response()->json([
                'message' => 'No logo found to delete'
            ], 404);
        }

        $logo->delete();

        activity()
            ->performedOn($campaign)
            ->causedBy(Auth::user())
            ->log('Campaign logo deleted');

        return response()->json([
            'message' => 'Logo deleted successfully'
        ]);
    }

    /**
     * Upload media files for the campaign.
     */
    public function uploadMedia(Request $request, Campaign $campaign): JsonResponse
    {
        $this->checkCampaignMediaPermission($campaign);

        $request->validate([
            'media' => 'required|file|mimes:png,jpg,jpeg,gif,webp,mp4,mov,avi,wmv,flv,webm|max:51200', // 50MB max
        ]);

        $media = $campaign->addMediaFromRequest('media')
            ->toMediaCollection(Campaign::OTHER_MEDIA_COLLECTION);

        activity()
            ->performedOn($campaign)
            ->causedBy(Auth::user())
            ->log('Campaign media uploaded');

        return response()->json([
            'message' => 'Media uploaded successfully',
            'media' => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
                'collection_name' => $media->collection_name,
            ]
        ]);
    }

    /**
     * List all media files for the campaign.
     */
    public function listMedia(Campaign $campaign): JsonResponse
    {
        $this->checkCampaignViewPermission($campaign);

        $mediaItems = $campaign->getMedia(Campaign::OTHER_MEDIA_COLLECTION);

        return response()->json([
            'media' => $mediaItems->map(function (Media $media) {
                return [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'url' => $media->getUrl(),
                    'collection_name' => $media->collection_name,
                    'created_at' => $media->created_at,
                ];
            })
        ]);
    }

    /**
     * Get a specific media file.
     */
    public function getMedia(Campaign $campaign, Media $media): JsonResponse
    {
        $this->checkCampaignViewPermission($campaign);

        // Check if media belongs to this campaign
        if ($media->model_id !== $campaign->id || $media->model_type !== Campaign::class) {
            return response()->json([
                'message' => 'Media not found'
            ], 404);
        }

        return response()->json([
            'media' => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
                'collection_name' => $media->collection_name,
                'created_at' => $media->created_at,
            ]
        ]);
    }

    /**
     * Update a specific media file.
     */
    public function updateMedia(Request $request, Campaign $campaign, Media $media): JsonResponse
    {
        $this->checkCampaignMediaPermission($campaign);

        // Check if media belongs to this campaign
        if ($media->model_id !== $campaign->id || $media->model_type !== Campaign::class) {
            return response()->json([
                'message' => 'Media not found'
            ], 404);
        }

        $request->validate([
            'media' => 'required|file|mimes:png,jpg,jpeg,gif,webp,mp4,mov,avi,wmv,flv,webm|max:51200', // 50MB max
        ]);

        // Delete old media
        $media->delete();

        // Add new media
        $newMedia = $campaign->addMediaFromRequest('media')
            ->toMediaCollection(Campaign::OTHER_MEDIA_COLLECTION);

        activity()
            ->performedOn($campaign)
            ->causedBy(Auth::user())
            ->log('Campaign media updated');

        return response()->json([
            'message' => 'Media updated successfully',
            'media' => [
                'id' => $newMedia->id,
                'name' => $newMedia->name,
                'file_name' => $newMedia->file_name,
                'mime_type' => $newMedia->mime_type,
                'size' => $newMedia->size,
                'url' => $newMedia->getUrl(),
                'collection_name' => $newMedia->collection_name,
            ]
        ]);
    }

    /**
     * Delete a specific media file.
     */
    public function deleteMedia(Campaign $campaign, Media $media): JsonResponse
    {
        $this->checkCampaignMediaPermission($campaign);

        // Check if media belongs to this campaign
        if ($media->model_id !== $campaign->id || $media->model_type !== Campaign::class) {
            return response()->json([
                'message' => 'Media not found'
            ], 404);
        }

        $media->delete();

        activity()
            ->performedOn($campaign)
            ->causedBy(Auth::user())
            ->log('Campaign media deleted');

        return response()->json([
            'message' => 'Media deleted successfully'
        ]);
    }

    /**
     * Check if user has permission to view campaign.
     */
    private function checkCampaignViewPermission(Campaign $campaign): void
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
    }

    /**
     * Check if user has permission to manage campaign media.
     */
    private function checkCampaignMediaPermission(Campaign $campaign): void
    {
        $user = Auth::user();

        // Check authorization - only campaign creators, admins, or users with manage campaigns permission can manage media
        if (!$user->can('manage campaigns') && !$user->is_admin && $campaign->creator_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot manage media for this campaign.');
        }
    }
}
