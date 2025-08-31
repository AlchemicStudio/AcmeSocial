// Common types and enums used across the AcmeSocial API

// Base UUID type for all entities
export type UUID = string;

// Date string type (ISO 8601 format)
export type DateString = string;

// Campaign Status Enum
export enum CampaignStatus {
  DRAFT = 0,
  PENDING = 1,
  APPROVED = 2,
  REJECTED = 3,
  COMPLETED = 4,
  CANCELLED = 5,
}

// Campaign Status Labels
export type CampaignStatusLabel = 
  | 'draft'
  | 'pending'
  | 'approved'
  | 'rejected'
  | 'completed'
  | 'cancelled';

// Donation Status Enum
export enum DonationStatus {
  PENDING = 0,
  COMPLETED = 1,
  FAILED = 2,
  REFUNDED = 3,
}

// Donation Status Labels
export type DonationStatusLabel = 
  | 'pending'
  | 'completed'
  | 'failed'
  | 'refunded';

// Donation Visibility Enum
export enum DonationVisibility {
  PUBLIC = 0,
  ANONYMOUS = 1,
}

// Donation Visibility Labels
export type DonationVisibilityLabel = 
  | 'public'
  | 'anonymous';

// Transaction Status
export type TransactionStatus = 
  | 'completed'
  | 'pending'
  | 'failed'
  | 'refunded';

// Payment Gateway Types
export type PaymentGateway = 
  | 'stripe'
  | 'paypal'
  | 'square';

// Currency Code (ISO 4217)
export type CurrencyCode = string; // 3 character uppercase code like 'USD', 'EUR'

// Pagination Meta Information
export interface PaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
}

// Pagination Links
export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

// Base Paginated Response
export interface PaginatedResponse<T> {
  data: T[];
  links: PaginationLinks;
  meta: PaginationMeta;
}

// Base API Error Response
export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

// Common Error Responses
export interface ValidationError extends ApiError {
  message: 'The given data was invalid.';
  errors: Record<string, string[]>;
}

export interface UnauthorizedError extends ApiError {
  message: 'Unauthenticated.';
}

export interface ForbiddenError extends ApiError {
  message: 'You do not have permission to perform this action.';
}

export interface NotFoundError extends ApiError {
  message: 'Resource not found.';
}

export interface ServerError extends ApiError {
  message: 'Server Error';
}

// Base Entity with common fields
export interface BaseEntity {
  id: UUID;
  created_at: DateString;
  updated_at: DateString;
  deleted_at?: DateString | null;
}

// Base API Response for single resources
export interface ApiResponse<T> {
  data?: T;
  message?: string;
}

// Query Parameters for Filtering
export interface BaseQueryParams {
  page?: number;
  per_page?: number;
}

// Permission Entity
export interface Permission {
  id: number;
  name: string;
  guard_name: string;
}

// Permission Response
export interface PermissionResponse {
  permissions: Permission[];
}

// Permission Assignment Response
export interface PermissionAssignmentResponse {
  message: string;
  permissions: Permission[];
}