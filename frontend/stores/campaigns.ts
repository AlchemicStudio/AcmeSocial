import { defineStore } from 'pinia'
import type {
  Campaign,
  CreateCampaignRequest,
  CampaignQueryParams,
  CampaignsResponse,
  CampaignStatistics,
  RejectCampaignRequest,
  CreateCampaignResponse,
  GetCampaignResponse,
  ApproveCampaignResponse,
  RejectCampaignResponse
} from '~/types/campaigns'
import type { UUID, CampaignStatus } from '~/types/common'

interface CampaignsState {
  // Campaign data
  campaigns: Campaign[]
  currentCampaign: Campaign | null
  campaignStatistics: CampaignStatistics | null
  
  // Pagination
  currentPage: number
  totalPages: number
  totalItems: number
  perPage: number
  
  // Loading states
  loading: boolean
  loadingCampaign: boolean
  loadingStatistics: boolean
  creating: boolean
  deleting: boolean
  approving: boolean
  rejecting: boolean
  
  // Error handling
  error: string | null
  campaignError: string | null
  
  // Filters
  filters: CampaignQueryParams
}

export const useCampaignsStore = defineStore('campaigns', {
  state: (): CampaignsState => ({
    // Campaign data
    campaigns: [],
    currentCampaign: null,
    campaignStatistics: null,
    
    // Pagination
    currentPage: 1,
    totalPages: 1,
    totalItems: 0,
    perPage: 15,
    
    // Loading states
    loading: false,
    loadingCampaign: false,
    loadingStatistics: false,
    creating: false,
    deleting: false,
    approving: false,
    rejecting: false,
    
    // Error handling
    error: null,
    campaignError: null,
    
    // Filters
    filters: {}
  }),

  getters: {
    // Get campaigns by status
    campaignsByStatus: (state) => (status: CampaignStatus) => {
      return state.campaigns.filter(campaign => campaign.status === status)
    },

    // Get campaigns by creator
    campaignsByCreator: (state) => (creatorId: UUID) => {
      return state.campaigns.filter(campaign => campaign.creator_id === creatorId)
    },

    // Get draft campaigns
    draftCampaigns: (state) => {
      return state.campaigns.filter(campaign => campaign.status === 0)
    },

    // Get pending campaigns
    pendingCampaigns: (state) => {
      return state.campaigns.filter(campaign => campaign.status === 1)
    },

    // Get approved campaigns
    approvedCampaigns: (state) => {
      return state.campaigns.filter(campaign => campaign.status === 2)
    },

    // Get rejected campaigns
    rejectedCampaigns: (state) => {
      return state.campaigns.filter(campaign => campaign.status === 3)
    },

    // Get completed campaigns
    completedCampaigns: (state) => {
      return state.campaigns.filter(campaign => campaign.status === 4)
    },

    // Get cancelled campaigns
    cancelledCampaigns: (state) => {
      return state.campaigns.filter(campaign => campaign.status === 5)
    },

    // Check if there are more pages
    hasNextPage: (state) => {
      return state.currentPage < state.totalPages
    },

    // Check if there are previous pages
    hasPreviousPage: (state) => {
      return state.currentPage > 1
    },

    // Get campaign completion percentage
    getCampaignProgress: (state) => (campaignId: UUID) => {
      const campaign = state.campaigns.find(c => c.id === campaignId)
      if (!campaign || campaign.goal_amount === 0) return 0
      return Math.round((campaign.current_amount / campaign.goal_amount) * 100)
    },

    // Check if any loading state is active
    isLoading: (state) => {
      return state.loading || state.loadingCampaign || state.creating || 
             state.deleting || state.approving || state.rejecting
    }
  },

  actions: {
    // Fetch campaigns with optional filters and pagination
    async fetchCampaigns(params: CampaignQueryParams = {}) {
        console.log("params: ", params)
        this.loading = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        // Build query parameters
        const queryParams = new URLSearchParams()
        
        if (params.status !== undefined) queryParams.append('status', params.status.toString())
        if (params.creator_id) queryParams.append('creator_id', params.creator_id)
        if (params.per_page) queryParams.append('per_page', params.per_page.toString())
        if (params.page) queryParams.append('page', params.page.toString())
        
        const queryString = queryParams.toString()
        const url = `/api/campaigns${queryString ? `?${queryString}` : ''}`
        
        const { data, status, error } = await useAsyncData('campaigns', () =>
          client(url, {
            headers: {
              Accept: "application/json",
              "Cache-Control": "no-cache",
            }
          })
        )
        
        // Wait for the request to complete
        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }
        
        if (status.value === 'error') {
          throw new Error(error.value?.message || 'Failed to fetch campaigns')
        }
        
        const response = data.value as CampaignsResponse
        if (response === null) {
            console.log(status, 'status')
            console.log("response: ", response)
            this.loading = false
            return response
        }
        this.campaigns = response.data
        this.currentPage = response.meta.current_page
        this.totalPages = response.meta.last_page
        this.totalItems = response.meta.total
        this.perPage = response.meta.per_page
        this.filters = params
        
        return response
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to fetch campaigns'
        this.error = errorMessage
        console.error('Error fetching campaigns:', err)
        throw err
      } finally {
        this.loading = false
      }
    },

    // Fetch a single campaign by ID
    async fetchCampaign(campaignId: UUID) {
      this.loadingCampaign = true
      this.campaignError = null
      
      try {
        const client = useSanctumClient()
        
        const { data, status, error } = await useAsyncData(`campaign-${campaignId}`, () =>
          client(`/api/campaigns/${campaignId}`, {
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
          throw new Error(error.value?.message || 'Failed to fetch campaign')
        }
        
        const campaign = data.value.data as GetCampaignResponse
        this.currentCampaign = campaign
        
        // Update campaign in campaigns list if it exists
        const index = this.campaigns.findIndex(c => c.id === campaignId)
        if (index !== -1) {
          this.campaigns[index] = campaign
        }
        
        return campaign
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to fetch campaign'
        this.campaignError = errorMessage
        console.error('Error fetching campaign:', err)
        throw err
      } finally {
        this.loadingCampaign = false
      }
    },

    // Create a new campaign
    async createCampaign(campaignData: CreateCampaignRequest) {
      this.creating = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        const { data, status, error } = await useAsyncData('create-campaign', () =>
          client('/api/campaigns', {
            method: 'POST',
            body: campaignData,
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
          throw new Error(error.value?.message || 'Failed to create campaign')
        }
        
        const newCampaign = data.value as CreateCampaignResponse
        this.campaigns.unshift(newCampaign)
        this.totalItems += 1
        
        return newCampaign
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to create campaign'
        this.error = errorMessage
        console.error('Error creating campaign:', err)
        throw err
      } finally {
        this.creating = false
      }
    },


    // Delete a campaign
    async deleteCampaign(campaignId: UUID) {
      this.deleting = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        const { status, error } = await useAsyncData(`delete-campaign-${campaignId}`, () =>
          client(`/api/campaigns/${campaignId}`, {
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
          throw new Error(error.value?.message || 'Failed to delete campaign')
        }
        
        // Remove campaign from campaigns list
        this.campaigns = this.campaigns.filter(c => c.id !== campaignId)
        this.totalItems -= 1
        
        // Clear current campaign if it was the deleted one
        if (this.currentCampaign?.id === campaignId) {
          this.currentCampaign = null
        }
        
        return true
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to delete campaign'
        this.error = errorMessage
        console.error('Error deleting campaign:', err)
        throw err
      } finally {
        this.deleting = false
      }
    },

    // Approve a campaign
    async approveCampaign(campaignId: UUID) {
      this.approving = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        const { data, status, error } = await useAsyncData(`approve-campaign-${campaignId}`, () =>
          client(`/api/campaigns/${campaignId}/approve`, {
            method: 'PUT',
            headers: {
              Accept: "application/json",
            }
          })
        )
        
        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }
        
        if (status.value === 'error') {
          throw new Error(error.value?.message || 'Failed to approve campaign')
        }
        
        const approvedCampaign = data.value as ApproveCampaignResponse
        
        // Update campaign in campaigns list
        const index = this.campaigns.findIndex(c => c.id === campaignId)
        if (index !== -1) {
          this.campaigns[index] = approvedCampaign
        }
        
        // Update current campaign if it's the same one
        if (this.currentCampaign?.id === campaignId) {
          this.currentCampaign = approvedCampaign
        }
        
        return approvedCampaign
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to approve campaign'
        this.error = errorMessage
        console.error('Error approving campaign:', err)
        throw err
      } finally {
        this.approving = false
      }
    },

    // Reject a campaign
    async rejectCampaign(campaignId: UUID, rejectData: RejectCampaignRequest) {
      this.rejecting = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        const { data, status, error } = await useAsyncData(`reject-campaign-${campaignId}`, () =>
          client(`/api/campaigns/${campaignId}/reject`, {
            method: 'PUT',
            body: rejectData,
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
          throw new Error(error.value?.message || 'Failed to reject campaign')
        }
        
        const rejectedCampaign = data.value as RejectCampaignResponse
        
        // Update campaign in campaigns list
        const index = this.campaigns.findIndex(c => c.id === campaignId)
        if (index !== -1) {
          this.campaigns[index] = rejectedCampaign
        }
        
        // Update current campaign if it's the same one
        if (this.currentCampaign?.id === campaignId) {
          this.currentCampaign = rejectedCampaign
        }
        
        return rejectedCampaign
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to reject campaign'
        this.error = errorMessage
        console.error('Error rejecting campaign:', err)
        throw err
      } finally {
        this.rejecting = false
      }
    },

    // Fetch campaign statistics
    async fetchCampaignStatistics(campaignId: UUID) {
      this.loadingStatistics = true
      this.error = null
      
      try {
        const client = useSanctumClient()
        
        const { data, status, error } = await useAsyncData(`campaign-statistics-${campaignId}`, () =>
          client(`/api/campaigns/${campaignId}/statistics`, {
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
          throw new Error(error.value?.message || 'Failed to fetch campaign statistics')
        }
        
        const statistics = data.value as CampaignStatistics
        this.campaignStatistics = statistics
        
        return statistics
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to fetch campaign statistics'
        this.error = errorMessage
        console.error('Error fetching campaign statistics:', err)
        throw err
      } finally {
        this.loadingStatistics = false
      }
    },

    // Clear errors
    clearErrors() {
      this.error = null
      this.campaignError = null
    },

    // Reset store state
    reset() {
      this.$reset()
    },

    // Set filters and refetch campaigns
    async setFilters(filters: CampaignQueryParams) {
      this.filters = { ...filters, page: 1 }
      await this.fetchCampaigns(this.filters)
    },

    // Navigate to next page
    async nextPage() {
      if (this.hasNextPage) {
        const nextPageFilters = { ...this.filters, page: this.currentPage + 1 }
        await this.fetchCampaigns(nextPageFilters)
      }
    },

    // Navigate to previous page
    async previousPage() {
      if (this.hasPreviousPage) {
        const prevPageFilters = { ...this.filters, page: this.currentPage - 1 }
        await this.fetchCampaigns(prevPageFilters)
      }
    },

    // Go to specific page
    async goToPage(page: number) {
      if (page >= 1 && page <= this.totalPages) {
        const pageFilters = { ...this.filters, page }
        await this.fetchCampaigns(pageFilters)
      }
    }
  }
})