// Main types export file for AcmeSocial API TypeScript definitions

// Export all common types and utilities
export * from './common';

// Export authentication and user types
export * from './auth';

// Export campaign types
export * from './campaigns';

// Export donation and transaction types
export * from './donations';

// Export permission management types
export * from './permissions';

// Re-export commonly used type combinations for convenience
export type { 
  // User-related
  User,
  UserBasic,
  CurrentUserResponse,
  UsersResponse
} from './auth';

export type {
  // Campaign-related
  Campaign,
  CampaignSummary,
  CampaignMedia,
  CampaignsResponse,
  CampaignStatistics
} from './campaigns';

export type {
  // Donation-related
  Donation,
  Transaction,
  DonationCampaign,
  DonationsResponse,
  TransactionsResponse
} from './donations';

export type {
  // Permission-related
  Permission,
  TypedPermission,
  PermissionName,
  PermissionResponse
} from './permissions';

export {
  // Enums for easy access
  CampaignStatus,
  DonationStatus,
  DonationVisibility
} from './common';