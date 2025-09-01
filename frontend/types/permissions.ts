// Permission management types for AcmeSocial API

import type { 
  UUID,
  Permission,
  PermissionResponse,
  PermissionAssignmentResponse
} from './common';

// User Permission Query Parameters
export interface UserPermissionParams {
  user: UUID;
}

// Assign Permissions Request
export interface AssignPermissionsRequest {
  permissions: string[]; // Array of permission names like ['manage campaigns', 'manage donations']
}

// Sync Permissions Request
export interface SyncPermissionsRequest {
  permissions: string[]; // Array of permission names - replaces all existing permissions
}

// Remove Permissions Request
export interface RemovePermissionsRequest {
  permissions: string[]; // Array of permission names to remove
}

// Get User Permissions Response
export type GetUserPermissionsResponse = PermissionResponse;

// Assign Permissions Response
export type AssignPermissionsResponse = PermissionAssignmentResponse;

// Sync Permissions Response
export type SyncPermissionsResponse = PermissionAssignmentResponse;

// Remove Permissions Response
export type RemovePermissionsResponse = PermissionAssignmentResponse;

// List All Permissions Response
export interface ListPermissionsResponse {
  data: Permission[];
}

// Available permission names in the system
export type PermissionName = 
  | 'manage campaigns'
  | 'manage donations'
  | 'manage users';

// Permission guard names
export type PermissionGuard = 'web';

// Enhanced Permission interface with typing
export interface TypedPermission extends Permission {
  name: PermissionName;
  guard_name: PermissionGuard;
}