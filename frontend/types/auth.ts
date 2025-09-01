// Authentication and User types for AcmeSocial API

import type { 
  UUID, 
  DateString, 
  BaseEntity, 
  BaseQueryParams, 
  PaginatedResponse 
} from './common';

// User Entity
export interface User extends BaseEntity {
  name: string;
  email: string;
  email_verified_at: DateString | null;
  is_admin: boolean;
}

// User with minimal information (used in relations)
export interface UserBasic {
  id: UUID;
  name: string;
  email?: string;
}

// Current User Response (from GET /user)
export type CurrentUserResponse = User;

// Create User Request
export interface CreateUserRequest {
  name: string;
  email: string;
  password: string;
  is_admin?: boolean;
}

// Update User Request
export interface UpdateUserRequest {
  name?: string;
  email?: string;
  password?: string;
  is_admin?: boolean;
}

// User Query Parameters
export interface UserQueryParams extends BaseQueryParams {
  name?: string;
  email?: string;
}

// User Search Query Parameters
export interface UserSearchParams {
  query: string;
}

// Paginated Users Response
export type UsersResponse = PaginatedResponse<User>;

// User Search Response
export interface UserSearchResponse {
  data: User[];
}

// Authentication credentials for login
export interface LoginCredentials {
  email: string;
  password: string;
}

// Registration data
export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

// Password reset request
export interface PasswordResetRequest {
  email: string;
}

// Password reset confirmation
export interface PasswordResetConfirmation {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}

// Email verification
export interface EmailVerificationRequest {
  id: UUID;
  hash: string;
  expires: string;
  signature: string;
}

// Authentication token response
export interface AuthTokenResponse {
  token: string;
  token_type: string;
  expires_in: number;
  user: User;
}

// Logout response
export interface LogoutResponse {
  message: string;
}