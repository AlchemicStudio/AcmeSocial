// Donation and Transaction types for AcmeSocial API

import type { 
  UUID, 
  DateString, 
  BaseEntity, 
  BaseQueryParams, 
  PaginatedResponse,
  DonationStatus,
  DonationStatusLabel,
  DonationVisibility,
  DonationVisibilityLabel,
  CurrencyCode,
  TransactionStatus,
  PaymentGateway
} from './common';

import type { UserBasic } from './auth';

// Campaign information in donation context (minimal)
export interface DonationCampaign {
  id: UUID;
  title: string;
  description: string;
  goal_amount: number; // Amount in cents
  current_amount: number; // Amount in cents
}

// Donation Entity (full details)
export interface Donation extends BaseEntity {
  campaign_id: UUID;
  donor_id: UUID;
  amount: number; // Amount in cents
  currency: CurrencyCode;
  message?: string | null; // max 1000 characters
  visibility: DonationVisibility;
  visibility_label: DonationVisibilityLabel;
  status: DonationStatus;
  status_label: DonationStatusLabel;
  campaign: DonationCampaign;
  donor: UserBasic;
}

// Transaction Entity
export interface Transaction extends BaseEntity {
  donation_id: UUID;
  transaction_reference: string;
  payment_gateway: PaymentGateway;
  gateway_transaction_id: string;
  amount: number; // Amount in cents
  currency: CurrencyCode;
  fee_amount: number; // Amount in cents
  status: TransactionStatus;
  status_message: string;
  processed_at: DateString;
  donation: Donation;
}

// Create Donation Request (Admin)
export interface CreateDonationRequest {
  campaign_id: UUID; // Must exist in campaigns table
  amount: number; // min 1, amount in cents
  currency?: CurrencyCode; // default: 'USD', 3 chars uppercase
  message?: string; // max 1000 characters
  visibility?: DonationVisibility; // default: public (0)
}

// Update Donation Request (Admin)
export interface UpdateDonationRequest {
  campaign_id?: UUID;
  amount?: number; // min 1, amount in cents
  currency?: CurrencyCode; // 3 chars uppercase
  message?: string; // max 1000 characters
  visibility?: DonationVisibility;
}

// Make Campaign Donation Request (Public)
export interface MakeDonationRequest {
  amount: number; // min 1, amount in cents
  currency?: CurrencyCode; // default: 'USD', 3 chars uppercase
  message?: string; // max 1000 characters
  visibility?: DonationVisibility; // default: public (0)
}

// Donation Query Parameters
export interface DonationQueryParams extends BaseQueryParams {
  campaign_id?: UUID;
  donor_id?: UUID;
  status?: DonationStatus;
  visibility?: DonationVisibility;
}

// Transaction Query Parameters
export interface TransactionQueryParams extends BaseQueryParams {
  donation_id?: UUID;
  payment_gateway?: PaymentGateway;
  status?: TransactionStatus;
}

// Paginated Donations Response (Admin)
export type DonationsResponse = PaginatedResponse<Donation>;

// Paginated Transactions Response (Admin)
export type TransactionsResponse = PaginatedResponse<Transaction>;

// Paginated Campaign Donations Response
export type CampaignDonationsResponse = PaginatedResponse<Donation>;

// Create Donation Response (Admin)
export type CreateDonationResponse = Donation;

// Update Donation Response (Admin)
export type UpdateDonationResponse = Donation;

// Get Donation Response (Admin)
export type GetDonationResponse = Donation;

// Make Donation Response (Public)
export type MakeDonationResponse = Donation;

// Get Campaign Donation Response
export type GetCampaignDonationResponse = Donation;

// Get Transaction Response (Admin)
export type GetTransactionResponse = Transaction;