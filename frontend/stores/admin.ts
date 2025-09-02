import { defineStore } from 'pinia'
import type {
  UUID,
  BaseEntity,
  BaseQueryParams,
  CampaignStatus,
  DonationStatus,
  DonationVisibility,
  CurrencyCode
} from '~/types/common'

// User creation/update request interface
export interface CreateUserRequest {
  name: string
  email: string
  password: string
  confirm_password: string
  is_admin: boolean
}

export interface UpdateUserRequest {
  name?: string
  email?: string
  password?: string
  is_admin?: boolean
}

// Permission interface
export interface Permission {
  id: number
  name: string
  guard_name: string
}

// User permissions management interface
export interface UserPermissions {
  permissions: Permission[]
}

// Permission assignment request
export interface AssignPermissionsRequest {
  permissions: string[]
}

// Transaction interface for admin management
export interface AdminTransaction extends BaseEntity {
  donation_id: UUID
  transaction_reference: string
  payment_gateway: string
  gateway_transaction_id: string
  amount: number
  currency: CurrencyCode
  fee_amount: number
  status: string
  status_message: string
  processed_at: string | null
  donation?: AdminDonation
}

// User interface for admin management
export interface AdminUser extends BaseEntity {
  name: string
  email: string
  email_verified_at: string | null
  is_admin: boolean
}

// Campaign interface for admin management
export interface AdminCampaign extends BaseEntity {
  title: string
  description: string
  goal_amount: number
  current_amount: number
  start_date: string
  end_date: string
  status: CampaignStatus
  status_label: string
  creator_id: UUID
  logo: string | null
  approved_at: string | null
  approved_by: UUID | null
  rejected_by: UUID | null
  rejected_at: string | null
  rejected_reason: string | null
  creator?: AdminUser
  approver?: AdminUser
  rejector?: AdminUser
}

// Donation interface for admin management
export interface AdminDonation extends BaseEntity {
  campaign_id: UUID
  donor_id: UUID
  amount: number
  currency: CurrencyCode
  message: string | null
  visibility: DonationVisibility
  visibility_label: string
  status: DonationStatus
  status_label: string
  campaign?: AdminCampaign
  donor?: AdminUser
}

// Admin store state interface
interface AdminState {
  // Users management
  users: AdminUser[]
  currentUser: AdminUser | null
  usersLoading: boolean
  usersError: string | null
  usersPagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  } | null

  // Permissions management
  permissions: Permission[]
  userPermissions: UserPermissions[] | []
  permissionsLoading: boolean
  permissionsError: string | null

  // Campaigns management  
  campaigns: AdminCampaign[]
  currentCampaign: AdminCampaign | null
  campaignsLoading: boolean
  campaignsError: string | null
  campaignsPagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  } | null

  // Donations management
  donations: AdminDonation[]
  currentDonation: AdminDonation | null
  donationsLoading: boolean
  donationsError: string | null
  donationsPagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  } | null
}

export const useAdminStore = defineStore('admin', {
  state: (): AdminState => ({
    // Users
    users: [],
    currentUser: null,
    usersLoading: false,
    usersError: null,
    usersPagination: null,

    // Permissions
    permissions: [],
    userPermissions: [],
    permissionsLoading: false,
    permissionsError: null,

    // Campaigns
    campaigns: [],
    currentCampaign: null,
    campaignsLoading: false,
    campaignsError: null,
    campaignsPagination: null,

    // Donations
    donations: [],
    currentDonation: null,
    donationsLoading: false,
    donationsError: null,
    donationsPagination: null
  }),

  getters: {
    // User getters
    adminUsers: (state) => state.users.filter(user => user.is_admin),
    regularUsers: (state) => state.users.filter(user => !user.is_admin),

    // Campaign getters
    pendingCampaigns: (state) => state.campaigns.filter(campaign => campaign.status === 1),
    approvedCampaigns: (state) => state.campaigns.filter(campaign => campaign.status === 2),
    rejectedCampaigns: (state) => state.campaigns.filter(campaign => campaign.status === 3),

    // Donation getters
    completedDonations: (state) => state.donations.filter(donation => donation.status === 1),
    pendingDonations: (state) => state.donations.filter(donation => donation.status === 0),

    // Loading states
    isLoading: (state) => state.usersLoading || state.campaignsLoading || state.donationsLoading || state.permissionsLoading
  },

  actions: {
    // User management actions
    async fetchUsers(params: BaseQueryParams = {}) {
      this.usersLoading = true
      this.usersError = null

      try {
        const client = useSanctumClient()
        const { data, status } = await useAsyncData('admin.users', () =>
          client('/api/users', {
            method: 'GET',
            params,
            headers: {
              Accept: 'application/json',
              'Cache-Control': 'no-cache'
            }
          })
        )

        if (status.value === 'success' && data.value) {
          this.users = data.value.data || data.value
          this.usersPagination = data.value.meta || null
        }
      } catch (error) {
        this.usersError = (error as Error).message || 'Failed to fetch users'
        console.error('Error fetching users:', error)
      } finally {
        this.usersLoading = false
      }
    },

    async fetchUser(userId: UUID) {
      try {
        const client = useSanctumClient()
        const { data } = await client(`/api/users/${userId}`, {
          method: 'GET',
          headers: { Accept: 'application/json' }
        })
        
        this.currentUser = data
        return data
      } catch (error) {
        this.usersError = (error as Error).message || 'Failed to fetch user'
        console.error('Error fetching user:', error)
        throw error
      }
    },

    async createUser(userData: CreateUserRequest) {
      try {
        const client = useSanctumClient()
        const { data } = await client('/api/users', {
          method: 'POST',
          body: userData,
          headers: { Accept: 'application/json' }
        })
        
        this.users.push(data)
        return data
      } catch (error) {
        this.usersError = (error as Error).message || 'Failed to create user'
        console.error('Error creating user:', error)
        throw error
      }
    },

    async updateUser(userId: UUID, userData: UpdateUserRequest) {
      try {
        const client = useSanctumClient()
        const { data } = await client(`/api/users/${userId}`, {
          method: 'PUT',
          body: userData,
          headers: { Accept: 'application/json' }
        })
        
        // Update user in state
        const userIndex = this.users.findIndex(u => u.id === userId)
        if (userIndex !== -1) {
          this.users[userIndex] = { ...this.users[userIndex], ...data }
        }
        
        return data
      } catch (error) {
        this.usersError = (error as Error).message || 'Failed to update user'
        console.error('Error updating user:', error)
        throw error
      }
    },

    async deleteUser(userId: UUID) {
      try {
        const client = useSanctumClient()
        await client(`/api/users/${userId}`, {
          method: 'DELETE',
          headers: { Accept: 'application/json' }
        })
        
        // Remove user from state
        this.users = this.users.filter(u => u.id !== userId)
      } catch (error) {
        this.usersError = (error as Error).message || 'Failed to delete user'
        console.error('Error deleting user:', error)
        throw error
      }
    },

    async searchUsers(query: string) {
      try {
        const client = useSanctumClient()
        const { data } = await client('/api/users/search', {
          method: 'GET',
          params: { query },
          headers: { Accept: 'application/json' }
        })
        
        return data
      } catch (error) {
        this.usersError = (error as Error).message || 'Failed to search users'
        console.error('Error searching users:', error)
        throw error
      }
    },

    // User permissions management
    async fetchUserPermissions(userId: UUID) {
      this.permissionsLoading = true
      this.permissionsError = null

      try {
        const client = useSanctumClient()

        const { data } = await client(`/api/users/${userId}/permissions`, {
          method: 'GET',
          headers: { Accept: 'application/json' }
        })

          console.log('User permissions:', data)
          console.log('All user permissions:', this.userPermissions)
        this.userPermissions[data.user_id] = data.all_permissions
        return data
      } catch (error) {
        this.permissionsError = (error as Error).message || 'Failed to fetch user permissions'
        console.error('Error fetching user permissions:', error)
        throw error
      } finally {
        this.permissionsLoading = false
      }
    },

    async assignPermissions(userId: UUID, permissions: string[]) {
      try {
        const client = useSanctumClient()
        const { data } = await client(`/api/users/${userId}/permissions`, {
          method: 'POST',
          body: { permissions },
          headers: { Accept: 'application/json' }
        })
        
        return data
      } catch (error) {
        this.usersError = (error as Error).message || 'Failed to assign permissions'
        console.error('Error assigning permissions:', error)
        throw error
      }
    },

    async syncPermissions(userId: UUID, permissions: string[]) {
      try {
        const client = useSanctumClient()
        const { data } = await client(`/api/users/${userId}/permissions`, {
          method: 'PUT',
          body: { permissions },
          headers: { Accept: 'application/json' }
        })
        
        return data
      } catch (error) {
        this.usersError = (error as Error).message || 'Failed to sync permissions'
        console.error('Error syncing permissions:', error)
        throw error
      }
    },

    async removePermissions(userId: UUID, permissions: string[]) {
      try {
        const client = useSanctumClient()
        const { data } = await client(`/api/users/${userId}/permissions`, {
          method: 'DELETE',
          body: { permissions },
          headers: { Accept: 'application/json' }
        })
        
        return data
      } catch (error) {
        this.usersError = (error as Error).message || 'Failed to remove permissions'
        console.error('Error removing permissions:', error)
        throw error
      }
    },

    // Campaign management actions
    async fetchCampaigns(params: BaseQueryParams = {}) {
      this.campaignsLoading = true
      this.campaignsError = null

      try {
        const client = useSanctumClient()
        const { data, status } = await useAsyncData('admin.campaigns', () =>
          client('/api/campaigns', {
            method: 'GET',
            params,
            headers: {
              Accept: 'application/json',
              'Cache-Control': 'no-cache'
            }
          })
        )

        if (status.value === 'success' && data.value) {
          this.campaigns = data.value.data || data.value
          this.campaignsPagination = data.value.meta || null
        }
      } catch (error) {
        this.campaignsError = (error as Error).message || 'Failed to fetch campaigns'
        console.error('Error fetching campaigns:', error)
      } finally {
        this.campaignsLoading = false
      }
    },

    async approveCampaign(campaignId: UUID) {
      try {
        const client = useSanctumClient()
        const { data } = await client(`/api/campaigns/${campaignId}/approve`, {
          method: 'PUT',
          headers: { Accept: 'application/json' }
        })

        // Update campaign in state
        const campaignIndex = this.campaigns.findIndex(c => c.id === campaignId)
        if (campaignIndex !== -1) {
          this.campaigns[campaignIndex] = { ...this.campaigns[campaignIndex], ...data }
        }
        
        return data
      } catch (error) {
        this.campaignsError = (error as Error).message || 'Failed to approve campaign'
        console.error('Error approving campaign:', error)
        throw error
      }
    },

    async rejectCampaign(campaignId: UUID, reason: string) {
      try {
        const client = useSanctumClient()
        const { data } = await client(`/api/campaigns/${campaignId}/reject`, {
          method: 'PUT',
          body: { reason },
          headers: { Accept: 'application/json' }
        })

        // Update campaign in state
        const campaignIndex = this.campaigns.findIndex(c => c.id === campaignId)
        if (campaignIndex !== -1) {
          this.campaigns[campaignIndex] = { ...this.campaigns[campaignIndex], ...data }
        }
        
        return data
      } catch (error) {
        this.campaignsError = (error as Error).message || 'Failed to reject campaign'
        console.error('Error rejecting campaign:', error)
        throw error
      }
    },

    async fetchCampaignStatistics(campaignId: UUID) {
      try {
        const client = useSanctumClient()
        const { data, status, error, refresh } = await useAsyncData('fetch_stat_for' + campaignId, () => {
            console.log('stat reqUESt')
            return client(`/api/campaigns/${campaignId}/statistics`, {
                  method: 'GET',
                  headers: { Accept: 'application/json' }
            })
          }
        );
        if (status.value === 'idle') {
            await refresh()
        }
        if (status.value === 'error') {
            console.error('Error fetching campaign statistics:', error.value)
            return null
        }
        if (status.value === 'success' && data.value) {
            console.log('Campaign statistics:', data.value)
            return data.value
        }
        return data.value
      } catch (error) {
        this.campaignsError = (error as Error).message || 'Failed to fetch campaign statistics'
        console.error('Error fetching campaign statistics:', error)
        throw error
      }
    },

    // Donation management actions
    async fetchDonations(params: BaseQueryParams = {}) {
      this.donationsLoading = true
      this.donationsError = null

      try {
        const client = useSanctumClient()
        const key = `admin.donations.${(params as BaseQueryParams).page || 1}.${(params as BaseQueryParams).per_page || ''}`
        const { data, status } = await useAsyncData(key, () =>
          client('/api/donations', {
            method: 'GET',
            params,
            headers: {
              Accept: 'application/json',
              'Cache-Control': 'no-cache'
            }
          })
        )

        if (status.value === 'success' && data.value) {
          this.donations = data.value.data || data.value
          this.donationsPagination = data.value.meta || null
        }
      } catch (error) {
        this.donationsError = (error as Error).message || 'Failed to fetch donations'
        console.error('Error fetching donations:', error)
      } finally {
        this.donationsLoading = false
      }
    },

    async createDonation(donationData: { campaign_id: UUID; amount: number; currency?: CurrencyCode; message?: string; visibility?: DonationVisibility }) {
      try {
        const client = useSanctumClient()
        const { data } = await client('/api/donations', {
          method: 'POST',
          body: donationData,
          headers: { Accept: 'application/json' }
        })
        
        this.donations.push(data)
        return data
      } catch (error) {
        this.donationsError = (error as Error).message || 'Failed to create donation'
        console.error('Error creating donation:', error)
        throw error
      }
    },

    async updateDonation(donationId: UUID, donationData: { campaign_id?: UUID; amount?: number; currency?: CurrencyCode; message?: string; visibility?: DonationVisibility }) {
      try {
        const client = useSanctumClient()
        const { data } = await client(`/api/donations/${donationId}`, {
          method: 'PUT',
          body: donationData,
          headers: { Accept: 'application/json' }
        })
        
        // Update donation in state
        const donationIndex = this.donations.findIndex(d => d.id === donationId)
        if (donationIndex !== -1) {
          this.donations[donationIndex] = { ...this.donations[donationIndex], ...data }
        }
        
        return data
      } catch (error) {
        this.donationsError = (error as Error).message || 'Failed to update donation'
        console.error('Error updating donation:', error)
        throw error
      }
    },

    async deleteDonation(donationId: UUID) {
      try {
        const client = useSanctumClient()
        await client(`/api/donations/${donationId}`, {
          method: 'DELETE',
          headers: { Accept: 'application/json' }
        })
        
        // Remove donation from state
        this.donations = this.donations.filter(d => d.id !== donationId)
      } catch (error) {
        this.donationsError = (error as Error).message || 'Failed to delete donation'
        console.error('Error deleting donation:', error)
        throw error
      }
    },

    async refundDonation(donationId: UUID) {
      try {
        const client = useSanctumClient()
        const { data } = await client(`/api/donations/${donationId}/refund`, {
          method: 'POST',
          headers: { Accept: 'application/json' }
        })

        // Update donation status in state
        const idx = this.donations.findIndex(d => d.id === donationId)
        if (idx !== -1) {
          this.donations[idx] = {
            ...this.donations[idx],
            ...(data || {}),
          }
        }
        return data
      } catch (error) {
        this.donationsError = (error as Error).message || 'Failed to refund donation'
        console.error('Error refunding donation:', error)
        throw error
      }
    },

    // Transactions management
    async fetchTransactions(params: BaseQueryParams = {}) {
      try {
        const client = useSanctumClient()
        const { data, status } = await useAsyncData('admin.transactions', () =>
          client('/api/transactions', {
            method: 'GET',
            params,
            headers: {
              Accept: 'application/json',
              'Cache-Control': 'no-cache'
            }
          })
        )

        if (status.value === 'success' && data.value) {
          return data.value
        }
      } catch (error) {
        console.error('Error fetching transactions:', error)
        throw error
      }
    },

    // Permissions management
    async fetchAllPermissions() {
      this.permissionsLoading = true
      this.permissionsError = null

      try {
        const client = useSanctumClient()
        const { data } = await client('/api/permissions', {
          method: 'GET',
          headers: { Accept: 'application/json' }
        })
        
        this.permissions = data
        return data
      } catch (error) {
        this.permissionsError = (error as Error).message || 'Failed to fetch permissions'
        console.error('Error fetching permissions:', error)
        throw error
      } finally {
        this.permissionsLoading = false
      }
    },

    // Utility actions
    async fetchDonation(donationId: UUID) {
      try {
        const client = useSanctumClient()
        const { data } = await client(`/api/donations/${donationId}`, {
          method: 'GET',
          headers: { Accept: 'application/json' }
        })
        
        this.currentDonation = data
        return data
      } catch (error) {
        this.donationsError = (error as Error).message || 'Failed to fetch donation'
        console.error('Error fetching donation:', error)
        throw error
      }
    },

    async fetchCampaign(campaignId: UUID) {
      try {
        const client = useSanctumClient()
        const { data } = await client(`/api/campaigns/${campaignId}`, {
          method: 'GET',
          headers: { Accept: 'application/json' }
        })
        
        this.currentCampaign = data
        return data
      } catch (error) {
        this.campaignsError = (error as Error).message || 'Failed to fetch campaign'
        console.error('Error fetching campaign:', error)
        throw error
      }
    },

    // Clear errors
    clearErrors() {
      this.usersError = null
      this.campaignsError = null
      this.donationsError = null
      this.permissionsError = null
    },

    // Reset store
    reset() {
      this.$reset()
    }
  }
})