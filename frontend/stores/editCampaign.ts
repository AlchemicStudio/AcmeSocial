import { defineStore } from 'pinia'
import type { UUID } from '~/types/common'

// Types for campaign creation
interface CreateCampaignRequest {
  title: string
  description: string
  goal_amount: number
  start_date: string
  end_date: string
  current_amount?: number
  status?: number
}

interface CampaignResponse {
  id: UUID
  title: string
  description: string
  goal_amount: number
  current_amount: number
  start_date: string
  end_date: string
  status: number
  status_label: string
  creator_id: UUID
  logo?: string
  medias?: MediaFile[]
  created_at: string
  updated_at: string
}

interface MediaFile {
  id: number
  name: string
  file_name: string
  mime_type: string
  size: number
  url: string
  collection_name: string
  created_at?: string
}

interface MediaUploadResponse {
  message: string
  media: MediaFile
}

interface EditCampaignState {
  // Campaign data
  currentCampaign: CampaignResponse | null
  
  // Form data
  formData: CreateCampaignRequest
  
  // Media files
  logoFile: File | null
  mediaFiles: File[]
  uploadedLogo: MediaFile | null
  uploadedMedia: MediaFile[]
  
  // Loading states
  creatingCampaign: boolean
  uploadingLogo: boolean
  uploadingMedia: boolean
  isLoading: boolean
  
  // Error handling
  campaignError: string | null
  logoError: string | null
  mediaError: string | null
  validationErrors: Record<string, string[]>
  
  // Progress tracking
  mediaUploadProgress: Record<string, number>
}

export const useEditCampaignStore = defineStore('editCampaign', {
  state: (): EditCampaignState => ({
    // Campaign data
    currentCampaign: null,
    
    // Form data
    formData: {
      title: '',
      description: '',
      goal_amount: 0,
      start_date: '',
      end_date: '',
      current_amount: 0,
      status: 1 // Default to pending
    },
    
    // Media files
    logoFile: null,
    mediaFiles: [],
    uploadedLogo: null,
    uploadedMedia: [],
    
    // Loading states
    creatingCampaign: false,
    uploadingLogo: false,
    uploadingMedia: false,
    isLoading: false,
    
    // Error handling
    campaignError: null,
    logoError: null,
    mediaError: null,
    validationErrors: {},
    
    // Progress tracking
    mediaUploadProgress: {}
  }),

  getters: {
    // Check if campaign creation is complete
    isCampaignCreated: (state): boolean => {
      return state.currentCampaign !== null
    },
    
    // Check if any uploads are in progress
    isUploading: (state): boolean => {
      return state.uploadingLogo || state.uploadingMedia
    },
    
    // Check if form is valid
    isFormValid: (state): boolean => {
      return (
        state.formData.title.trim() !== '' &&
        state.formData.description.trim() !== '' &&
        state.formData.goal_amount > 0 &&
        state.formData.start_date !== '' &&
        state.formData.end_date !== '' &&
        new Date(state.formData.end_date) >= new Date(state.formData.start_date)
      )
    },
    
    // Get total media upload progress
    totalUploadProgress: (state): number => {
      const progressValues = Object.values(state.mediaUploadProgress)
      if (progressValues.length === 0) return 0
      return progressValues.reduce((sum, progress) => sum + progress, 0) / progressValues.length
    },
    
    // Check if all media is uploaded
    allMediaUploaded: (state): boolean => {
      return state.mediaFiles.length === state.uploadedMedia.length
    }
  },

  actions: {
    // Set form data
    setFormData(data: Partial<CreateCampaignRequest>) {
      this.formData = { ...this.formData, ...data }
      this.validationErrors = {}
    },

    // Set logo file
    setLogoFile(file: File | null) {
      this.logoFile = file
      this.logoError = null
    },

    // Add media files
    addMediaFiles(files: File[]) {
      this.mediaFiles = [...this.mediaFiles, ...files]
      this.mediaError = null
    },

    // Remove media file
    removeMediaFile(index: number) {
      const fileName = this.mediaFiles[index]?.name
      this.mediaFiles.splice(index, 1)
      // Remove corresponding upload progress
      if (fileName && this.mediaUploadProgress[fileName]) {
        const { [fileName]: _, ...newProgress } = this.mediaUploadProgress
        this.mediaUploadProgress = newProgress
      }
    },

    // Clear all errors
    clearErrors() {
      this.campaignError = null
      this.logoError = null
      this.mediaError = null
      this.validationErrors = {}
    },

    // Reset the entire store
    reset() {
      this.currentCampaign = null
      this.formData = {
        title: '',
        description: '',
        goal_amount: 0,
        start_date: '',
        end_date: '',
        current_amount: 0,
        status: 1
      }
      this.logoFile = null
      this.mediaFiles = []
      this.uploadedLogo = null
      this.uploadedMedia = []
      this.clearErrors()
      this.mediaUploadProgress = {}
    },

    // Step 1: Create campaign
    async createCampaign() {
      if (!this.isFormValid) {
        this.campaignError = 'Please fill in all required fields correctly'
        return false
      }

      this.creatingCampaign = true
      this.isLoading = true
      this.campaignError = null
      this.validationErrors = {}

      try {
        const client = useSanctumClient()

        const { data, status, error } = await useAsyncData('create-campaign', () =>
          client('/api/campaigns', {
            method: 'POST',
            body: this.formData,
            headers: {
              Accept: 'application/json',
              'Content-Type': 'application/json'
            }
          })
        )

        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }

        if (status.value === 'error') {
          if (error.value?.statusCode === 422) {
            // Handle validation errors
            const errorData = error.value.data
            if (errorData && errorData.errors) {
              this.validationErrors = errorData.errors
            }
            throw new Error(errorData?.message || 'Validation failed')
          }
          throw new Error(error.value?.message || 'Failed to create campaign')
        }

        this.currentCampaign = data.value as CampaignResponse
        return true
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to create campaign'
        this.campaignError = errorMessage
        console.error('Error creating campaign:', err)
        return false
      } finally {
        this.creatingCampaign = false
        this.isLoading = false
      }
    },

    // Step 2a: Upload campaign logo
    async uploadLogo() {
      if (!this.currentCampaign) {
        this.logoError = 'Campaign must be created before uploading logo'
        return false
      }

      if (!this.logoFile) {
        this.logoError = 'No logo file selected'
        return false
      }

      this.uploadingLogo = true
      this.logoError = null

      try {
        const client = useSanctumClient()
        const formData = new FormData()
        formData.append('logo', this.logoFile)

        const { data, status, error } = await useAsyncData(
          `upload-logo-${this.currentCampaign.id}`,
          () => client(`/api/campaigns/${this.currentCampaign!.id}/logo`, {
            method: 'POST',
            body: formData,
            headers: {
              Accept: 'application/json'
              // Don't set Content-Type for FormData, let the browser set it
            }
          })
        )

        while (status.value === 'idle') {
          await new Promise(resolve => setTimeout(resolve, 10))
        }

        if (status.value === 'error') {
          throw new Error(error.value?.message || 'Failed to upload logo')
        }

        const response = data.value as MediaUploadResponse
        this.uploadedLogo = response.media
        
        // Update current campaign with logo URL
        if (this.currentCampaign) {
          this.currentCampaign.logo = response.media.url
        }

        return true
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to upload logo'
        this.logoError = errorMessage
        console.error('Error uploading logo:', err)
        return false
      } finally {
        this.uploadingLogo = false
      }
    },

    // Step 2b: Upload campaign media files
    async uploadMedia() {
      if (!this.currentCampaign) {
        this.mediaError = 'Campaign must be created before uploading media'
        return false
      }

      if (this.mediaFiles.length === 0) {
        return true // No media to upload is not an error
      }

      this.uploadingMedia = true
      this.mediaError = null
      this.uploadedMedia = []

      try {
        // Upload files one by one to track progress
        for (let i = 0; i < this.mediaFiles.length; i++) {
          const file = this.mediaFiles[i]
          const fileName = file.name

          // Initialize progress
          this.mediaUploadProgress[fileName] = 0

          try {
            const client = useSanctumClient()
            const formData = new FormData()
            formData.append('media', file)

            const { data, status, error } = await useAsyncData(
              `upload-media-${this.currentCampaign.id}-${i}`,
              () => client(`/api/campaigns/${this.currentCampaign!.id}/media`, {
                method: 'POST',
                body: formData,
                headers: {
                  Accept: 'application/json'
                }
              })
            )

            while (status.value === 'idle') {
              await new Promise(resolve => setTimeout(resolve, 10))
            }

            if (status.value === 'error') {
              throw new Error(error.value?.message || `Failed to upload ${fileName}`)
            }

            const response = data.value as MediaUploadResponse
            this.uploadedMedia.push(response.media)
            this.mediaUploadProgress[fileName] = 100

            // Update current campaign media array
            if (this.currentCampaign) {
              if (!this.currentCampaign.medias) {
                this.currentCampaign.medias = []
              }
              this.currentCampaign.medias.push(response.media)
            }
          } catch (err) {
            console.error(`Error uploading ${fileName}:`, err)
            this.mediaUploadProgress[fileName] = -1 // Mark as failed
            throw err
          }
        }

        return true
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to upload media files'
        this.mediaError = errorMessage
        console.error('Error uploading media:', err)
        return false
      } finally {
        this.uploadingMedia = false
      }
    },

    // Complete campaign creation process (create + upload all media)
    async createCampaignWithMedia() {
      // Step 1: Create campaign
      const campaignCreated = await this.createCampaign()
      if (!campaignCreated) {
        return false
      }

      // Step 2a: Upload logo if provided
      if (this.logoFile) {
        const logoUploaded = await this.uploadLogo()
        if (!logoUploaded) {
          console.warn('Logo upload failed, but campaign was created')
        }
      }

      // Step 2b: Upload media files if provided
      if (this.mediaFiles.length > 0) {
        const mediaUploaded = await this.uploadMedia()
        if (!mediaUploaded) {
          console.warn('Media upload failed, but campaign was created')
        }
      }

      return true
    }
  }
})