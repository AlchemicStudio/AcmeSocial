import { defineStore } from 'pinia'
import {
  DonationStatus,
  DonationVisibility
} from '~/types/common'
import type {
  UUID,
  DonationStatusLabel,
  DonationVisibilityLabel,
  CurrencyCode,
  PaginatedResponse,
  BaseEntity,
  BaseQueryParams
} from '~/types/common'

// User interface for donor information
interface DonorUser {
  id: UUID
  name: string
}

// Campaign interface for donation context
interface DonationCampaign {
  id: UUID
  title: string
  description: string
  goal_amount: number
  current_amount: number
}

// Main Donation interface based on API documentation
export interface Donation extends BaseEntity {
  campaign_id: UUID
  donor_id: UUID
  amount: number
  currency: CurrencyCode
  message: string | null
  visibility: DonationVisibility
  visibility_label: DonationVisibilityLabel
  status: DonationStatus
  status_label: DonationStatusLabel
  campaign?: DonationCampaign
  donor?: DonorUser
}

// Create donation request interface
export interface CreateDonationRequest {
  campaign_id?: UUID  // Only for admin creation
  amount: number
  currency?: CurrencyCode
  message?: string
  visibility?: DonationVisibility
}

// Update donation request interface (admin only)
export interface UpdateDonationRequest {
  campaign_id?: UUID
  amount?: number
  currency?: CurrencyCode
  message?: string
  visibility?: DonationVisibility
}

// Campaign donation request interface (for regular users)
export interface CampaignDonationRequest {
  amount: number
  currency?: CurrencyCode
  message?: string
  visibility?: DonationVisibility
}

// Query parameters for filtering donations
export interface DonationQueryParams extends BaseQueryParams {
  status?: DonationStatus
  campaign_id?: UUID
  donor_id?: UUID
  visibility?: DonationVisibility
  page?: number
  per_page?: number
}

// API Response types
export type DonationsResponse = PaginatedResponse<Donation>
export type CreateDonationResponse = Donation
export type GetDonationResponse = Donation
export type UpdateDonationResponse = Donation

// Store state interface
interface DonationState {
  // Donation data
  donations: Donation[]
  currentDonation: Donation | null
  campaignDonations: Donation[]
  
  // Pagination
  currentPage: number
  totalPages: number
  totalItems: number
  perPage: number
  
  // Loading states
  loading: boolean
  loadingDonation: boolean
  loadingCampaignDonations: boolean
  creating: boolean
  updating: boolean
  deleting: boolean
  makingDonation: boolean
  
  // Error handling
  error: string | null
  donationError: string | null
  
  // Filters
  filters: DonationQueryParams
}

export const useDonationStore = defineStore('donation', {
  state: (): DonationState => ({
    // Donation data
    donations: [],
    currentDonation: null,
    campaignDonations: [],
    
    // Pagination
    currentPage: 1,
    totalPages: 1,
    totalItems: 0,
    perPage: 15,
    
    // Loading states
    loading: false,
    loadingDonation: false,
    loadingCampaignDonations: false,
    creating: false,
    updating: false,
    deleting: false,
    makingDonation: false,
    
    // Error handling
    error: null,
    donationError: null,
    
    // Filters
    filters: {}
  }),

  getters: {
    // Get donations by status
    donationsByStatus: (state) => (status: DonationStatus) => {
      return state.donations.filter(donation => donation.status === status)
    },

    // Get donations by campaign
    donationsByCampaign: (state) => (campaignId: UUID) => {
      return state.donations.filter(donation => donation.campaign_id === campaignId)
    },

    // Get donations by donor
    donationsByDonor: (state) => (donorId: UUID) => {
      return state.donations.filter(donation => donation.donor_id === donorId)
    },

    // Get donations by visibility
    donationsByVisibility: (state) => (visibility: DonationVisibility) => {
      return state.donations.filter(donation => donation.visibility === visibility)
    },

    // Get pending donations
    pendingDonations: (state) => {
      return state.donations.filter(donation => donation.status === DonationStatus.PENDING)
    },

    // Get completed donations
    completedDonations: (state) => {
      return state.donations.filter(donation => donation.status === DonationStatus.COMPLETED)
    },

    // Get failed donations
    failedDonations: (state) => {
      return state.donations.filter(donation => donation.status === DonationStatus.FAILED)
    },

    // Get refunded donations
    refundedDonations: (state) => {
      return state.donations.filter(donation => donation.status === DonationStatus.REFUNDED)
    },

    // Get public donations
    publicDonations: (state) => {
      return state.donations.filter(donation => donation.visibility === DonationVisibility.PUBLIC)
    },

    // Get anonymous donations
    anonymousDonations: (state) => {
      return state.donations.filter(donation => donation.visibility === DonationVisibility.ANONYMOUS)
    },

    // Calculate total donation amount
    totalDonationAmount: (state) => {
      return state.donations
        .filter(donation => donation.status === DonationStatus.COMPLETED)
        .reduce((total, donation) => total + donation.amount, 0)
    },

    // Calculate total campaign donation amount
    totalCampaignDonationAmount: (state) => {
      return state.campaignDonations
        .filter(donation => donation.status === DonationStatus.COMPLETED)
        .reduce((total, donation) => total + donation.amount, 0)
    },

    // Get average donation amount
    averageDonationAmount: (state) => {
      const completedDonations = state.donations.filter(donation => donation.status === DonationStatus.COMPLETED)
      if (completedDonations.length === 0) return 0
      const total = completedDonations.reduce((sum, donation) => sum + donation.amount, 0)
      return Math.round(total / completedDonations.length)
    },

    // Count donations by status
    donationCountByStatus: (state) => {
      return {
        pending: state.donations.filter(d => d.status === DonationStatus.PENDING).length,
        completed: state.donations.filter(d => d.status === DonationStatus.COMPLETED).length,
        failed: state.donations.filter(d => d.status === DonationStatus.FAILED).length,
        refunded: state.donations.filter(d => d.status === DonationStatus.REFUNDED).length
      }
    },

    // Check pagination
    hasNextPage: (state) => {
      return state.currentPage < state.totalPages
    },

    hasPreviousPage: (state) => {
      return state.currentPage > 1
    },

    // Check if any loading state is active
    isLoading: (state) => {
      return state.loading || state.loadingDonation || state.loadingCampaignDonations ||
             state.creating || state.updating || state.deleting || state.makingDonation
    }
  },

  actions: {
    // Fetch all donations (admin only)
    async fetchDonations(params: DonationQueryParams = {}) {
      this.loading = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        // Build query parameters
        const queryParams = new URLSearchParams()
        
        if (params.status !== undefined) queryParams.append('status', params.status.toString())
        if (params.campaign_id) queryParams.append('campaign_id', params.campaign_id)
        if (params.donor_id) queryParams.append('donor_id', params.donor_id)
        if (params.visibility !== undefined) queryParams.append('visibility', params.visibility.toString())
        if (params.per_page) queryParams.append('per_page', params.per_page.toString())
        if (params.page) queryParams.append('page', params.page.toString())
        
        const queryString = queryParams.toString()
        const url = `/api/donations${queryString ? `?${queryString}` : ''}`
        
        const { data, status, error } = await useAsyncData('donations', () =>
          client(url, {
            headers: {
              Accept: "application/json",
              "Cache-Control": "no-cache",
            }
          })
        )
        
        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }
        
        if (status.value === 'error') {
          throw new Error(error.value?.message || 'Failed to fetch donations')
        }
        
        const response = data.value as DonationsResponse
        
        this.donations = response.data
        this.currentPage = response.meta.current_page
        this.totalPages = response.meta.last_page
        this.totalItems = response.meta.total
        this.perPage = response.meta.per_page
        this.filters = params
        
        return response
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to fetch donations'
        this.error = errorMessage
        console.error('Error fetching donations:', err)
        throw err
      } finally {
        this.loading = false
      }
    },

    // Fetch a single donation by ID (admin only)
    async fetchDonation(donationId: UUID) {
      this.loadingDonation = true
      this.donationError = null
      
      try {
        const client = useSanctumClient()
        
        const { data, status, error } = await useAsyncData(`donation-${donationId}`, () =>
          client(`/api/donations/${donationId}`, {
            headers: {
              Accept: "application/json",
              "Cache-Control": "no-cache",
            }
          })
        )
        
        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }
        
        if (status.value === 'error') {
          throw new Error(error.value?.message || 'Failed to fetch donation')
        }
        
        const donation = data.value as GetDonationResponse
        this.currentDonation = donation
        
        // Update donation in donations list if it exists
        const index = this.donations.findIndex(d => d.id === donationId)
        if (index !== -1) {
          this.donations[index] = donation
        }
        
        return donation
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to fetch donation'
        this.donationError = errorMessage
        console.error('Error fetching donation:', err)
        throw err
      } finally {
        this.loadingDonation = false
      }
    },

    // Create a new donation (admin only)
    async createDonation(donationData: CreateDonationRequest) {
      this.creating = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        const { data, status, error } = await useAsyncData('create-donation', () =>
          client('/api/donations', {
            method: 'POST',
            body: donationData,
            headers: {
              Accept: "application/json",
              "Content-Type": "application/json",
            }
          })
        )
        
        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }
        
        if (status.value === 'error') {
          throw new Error(error.value?.message || 'Failed to create donation')
        }
        
        const newDonation = data.value as CreateDonationResponse
        this.donations.unshift(newDonation)
        this.totalItems += 1
        
        return newDonation
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to create donation'
        this.error = errorMessage
        console.error('Error creating donation:', err)
        throw err
      } finally {
        this.creating = false
      }
    },

    // Update a donation (admin only)
    async updateDonation(donationId: UUID, donationData: UpdateDonationRequest) {
      this.updating = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        const { data, status, error } = await useAsyncData(`update-donation-${donationId}`, () =>
          client(`/api/donations/${donationId}`, {
            method: 'PUT',
            body: donationData,
            headers: {
              Accept: "application/json",
              "Content-Type": "application/json",
            }
          })
        )
        
        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }
        
        if (status.value === 'error') {
          throw new Error(error.value?.message || 'Failed to update donation')
        }
        
        const updatedDonation = data.value as UpdateDonationResponse
        
        // Update donation in donations list
        const index = this.donations.findIndex(d => d.id === donationId)
        if (index !== -1) {
          this.donations[index] = updatedDonation
        }
        
        // Update current donation if it's the same one
        if (this.currentDonation?.id === donationId) {
          this.currentDonation = updatedDonation
        }
        
        return updatedDonation
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to update donation'
        this.error = errorMessage
        console.error('Error updating donation:', err)
        throw err
      } finally {
        this.updating = false
      }
    },

    // Delete a donation (admin only)
    async deleteDonation(donationId: UUID) {
      this.deleting = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        const { status, error } = await useAsyncData(`delete-donation-${donationId}`, () =>
          client(`/api/donations/${donationId}`, {
            method: 'DELETE',
            headers: {
              Accept: "application/json",
            }
          })
        )
        
        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }
        
        if (status.value === 'error') {
          throw new Error(error.value?.message || 'Failed to delete donation')
        }
        
        // Remove donation from donations list
        this.donations = this.donations.filter(d => d.id !== donationId)
        this.totalItems -= 1
        
        // Clear current donation if it was the deleted one
        if (this.currentDonation?.id === donationId) {
          this.currentDonation = null
        }
        
        return true
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to delete donation'
        this.error = errorMessage
        console.error('Error deleting donation:', err)
        throw err
      } finally {
        this.deleting = false
      }
    },

    // Make a donation to a campaign (regular users)
    async makeCampaignDonation(campaignId: UUID, donationData: CampaignDonationRequest) {
      this.makingDonation = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        const { data, status, error } = await useAsyncData(`make-donation-${campaignId}`, () =>
          client(`/api/campaigns/${campaignId}/donations`, {
            method: 'POST',
            body: donationData,
            headers: {
              Accept: "application/json",
              "Content-Type": "application/json",
            }
          })
        )
        
        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }
        
        if (status.value === 'error') {
          throw new Error(error.value?.message || 'Failed to make donation')
        }
        
        const newDonation = data.value as Donation
        
        // Add to campaign donations list
        this.campaignDonations.unshift(newDonation)
        
        // Add to main donations list if it exists
        if (this.donations.length > 0) {
          this.donations.unshift(newDonation)
          this.totalItems += 1
        }
        
        return newDonation
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to make donation'
        this.error = errorMessage
        console.error('Error making donation:', err)
        throw err
      } finally {
        this.makingDonation = false
      }
    },

    // Fetch donations for a specific campaign
    async fetchCampaignDonations(campaignId: UUID, params: DonationQueryParams = {}) {
      this.loadingCampaignDonations = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        // Build query parameters
        const queryParams = new URLSearchParams()
        
        if (params.per_page) queryParams.append('per_page', params.per_page.toString())
        if (params.page) queryParams.append('page', params.page.toString())
        
        const queryString = queryParams.toString()
        const url = `/api/campaigns/${campaignId}/donations${queryString ? `?${queryString}` : ''}`
        
        const { data, status, error } = await useAsyncData(`campaign-donations-${campaignId}`, () =>
          client(url, {
            headers: {
              Accept: "application/json",
              "Cache-Control": "no-cache",
            }
          })
        )
        
        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }
        
        if (status.value === 'error') {
          throw new Error(error.value?.message || 'Failed to fetch campaign donations')
        }
        
        const response = data.value as DonationsResponse
        this.campaignDonations = response.data
        
        return response
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to fetch campaign donations'
        this.error = errorMessage
        console.error('Error fetching campaign donations:', err)
        throw err
      } finally {
        this.loadingCampaignDonations = false
      }
    },

    // Get a specific donation from a campaign
    async fetchCampaignDonation(campaignId: UUID, donationId: UUID) {
      this.loadingDonation = true
      this.donationError = null
      
      try {
        const client = useSanctumClient()
        
        const { data, status, error } = await useAsyncData(`campaign-donation-${campaignId}-${donationId}`, () =>
          client(`/api/campaigns/${campaignId}/donations/${donationId}`, {
            headers: {
              Accept: "application/json",
              "Cache-Control": "no-cache",
            }
          })
        )
        
        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }
        
        if (status.value === 'error') {
          throw new Error(error.value?.message || 'Failed to fetch campaign donation')
        }
        
        const donation = data.value as GetDonationResponse
        this.currentDonation = donation
        
        return donation
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to fetch campaign donation'
        this.donationError = errorMessage
        console.error('Error fetching campaign donation:', err)
        throw err
      } finally {
        this.loadingDonation = false
      }
    },

    // Clear errors
    clearErrors() {
      this.error = null
      this.donationError = null
    },

    // Reset store state
    reset() {
      this.$reset()
    },

    // Set filters and refetch donations
    async setFilters(filters: DonationQueryParams) {
      this.filters = { ...filters, page: 1 }
      await this.fetchDonations(this.filters)
    },

    // Navigate to next page
    async nextPage() {
      if (this.hasNextPage) {
        const nextPageFilters = { ...this.filters, page: this.currentPage + 1 }
        await this.fetchDonations(nextPageFilters)
      }
    },

    // Navigate to previous page
    async previousPage() {
      if (this.hasPreviousPage) {
        const prevPageFilters = { ...this.filters, page: this.currentPage - 1 }
        await this.fetchDonations(prevPageFilters)
      }
    },

    // Go to specific page
    async goToPage(page: number) {
      if (page >= 1 && page <= this.totalPages) {
        const pageFilters = { ...this.filters, page }
        await this.fetchDonations(pageFilters)
      }
    }
  }
})