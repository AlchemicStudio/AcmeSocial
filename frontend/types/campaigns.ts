// Campaign types for AcmeSocial API

import type { 
  UUID, 
  DateString, 
  BaseEntity, 
  BaseQueryParams, 
  PaginatedResponse,
  CampaignStatus,
  CampaignStatusLabel
} from './common';

import type { UserBasic } from './auth';

// Media Entity for campaign attachments
export interface CampaignMedia {
  id: UUID;
  url: string;
  type: string; // MIME type like 'image/jpeg'
}

// Campaign Entity (full details)
export interface Campaign extends BaseEntity {
  title: string;
  description: string;
  goal_amount: number; // Amount in cents
  current_amount: number; // Amount in cents
  start_date: string; // Date string format YYYY-MM-DD
  end_date: string; // Date string format YYYY-MM-DD
  status: CampaignStatus;
  status_label: CampaignStatusLabel;
  creator_id: UUID;
  logo?: string | null; // URL to logo image
  medias: CampaignMedia[];
  approved_at?: DateString | null;
  approved_by?: UUID | null;
  rejected_by?: UUID | null;
  rejected_at?: DateString | null;
  rejected_reason?: string | null;
  creator: UserBasic;
  approver?: UserBasic | null;
  rejector?: UserBasic | null;
}

// Campaign with minimal information (for lists)
export interface CampaignSummary {
  id: UUID;
  title: string;
  description: string;
  goal_amount: number;
  current_amount: number;
  status: CampaignStatus;
  status_label: CampaignStatusLabel;
  creator: UserBasic;
}

// Create Campaign Request
export interface CreateCampaignRequest {
  title: string; // max 255 characters
  description: string;
  goal_amount: number; // min 1, amount in cents
  start_date: string; // YYYY-MM-DD format
  end_date: string; // YYYY-MM-DD format, must be >= start_date
  current_amount?: number; // min 0, amount in cents
  status?: CampaignStatus;
  creator_id?: UUID;
  approved_at?: DateString;
  approved_by?: UUID;
  rejected_by?: UUID;
  rejected_reason?: string;
}

// Update Campaign Request
export interface UpdateCampaignRequest {
  title?: string; // max 255 characters
  description?: string;
  goal_amount?: number; // min 1, amount in cents
  start_date?: string; // YYYY-MM-DD format
  end_date?: string; // YYYY-MM-DD format, must be >= start_date
  current_amount?: number; // min 0, amount in cents
  status?: CampaignStatus;
  creator_id?: UUID;
  approved_at?: DateString;
  approved_by?: UUID;
  rejected_by?: UUID;
  rejected_reason?: string;
}

// Campaign Query Parameters
export interface CampaignQueryParams extends BaseQueryParams {
  status?: CampaignStatus;
  creator_id?: UUID;
}

// Paginated Campaigns Response
export type CampaignsResponse = PaginatedResponse<Campaign>;

// Campaign Statistics Response
export interface CampaignStatistics {
  total_donations: number;
  total_amount: number; // Amount in cents
  unique_donors: number;
  average_donation: number; // Amount in cents
  completion_percentage: number; // Percentage (0-100)
  statistics: {
    labels: string[]; // Date labels
    datasets: Array<{
      label: string; // 'Daily Quantity' or 'Daily Amount'
      data: number[];
    }>;
  };
}

// Campaign Rejection Request
export interface RejectCampaignRequest {
  reason: string; // max 1000 characters
}

// Campaign Approval Response
export type ApproveCampaignResponse = Campaign;

// Campaign Rejection Response  
export type RejectCampaignResponse = Campaign;

// Campaign Creation Response
export type CreateCampaignResponse = Campaign;

// Campaign Update Response
export type UpdateCampaignResponse = Campaign;

// Get Campaign Response
export type GetCampaignResponse = Campaign;