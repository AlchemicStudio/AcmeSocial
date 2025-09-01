<template lang="pug">
v-card.campaign-edit-form(elevation="2")
  v-card-title
    v-icon.me-2(color="primary") {{ isEditing ? 'mdi-pencil' : 'mdi-plus' }}
    | {{ isEditing ? 'Edit Campaign' : 'Create New Campaign' }}
  
  v-card-text
    v-form(ref="form" v-model="isFormValid" @submit.prevent="handleSubmit")
      v-row
        // Title Field
        v-col(cols="12")
          v-text-field(
            v-model="editCampaignStore.formData.title"
            label="Campaign Title"
            variant="outlined"
            :rules="titleRules"
            :error-messages="getFieldError('title')"
            required
            counter="255"
            clearable
          )
        
        // Description Field
        v-col(cols="12")
          v-textarea(
            v-model="editCampaignStore.formData.description"
            label="Campaign Description"
            variant="outlined"
            :rules="descriptionRules"
            :error-messages="getFieldError('description')"
            required
            rows="4"
            counter="2000"
            clearable
          )
        
        // Goal Amount Field
        v-col(cols="12" md="6")
          v-text-field(
            v-model.number="goalAmountDisplay"
            label="Goal Amount ($)"
            variant="outlined"
            type="number"
            :rules="goalAmountRules"
            :error-messages="getFieldError('goal_amount')"
            required
            prefix="$"
            min="1"
            step="0.01"
            @update:model-value="updateGoalAmount"
          )
        
        // Current Amount Field (for editing)
        v-col(cols="12" md="6" v-if="isEditing")
          v-text-field(
            v-model.number="currentAmountDisplay"
            label="Current Amount ($)"
            variant="outlined"
            type="number"
            prefix="$"
            min="0"
            step="0.01"
            readonly
            hint="This field is automatically updated from donations"
          )
        
        // Start Date Field
        v-col(cols="12" md="6")
          v-text-field(
            v-model="editCampaignStore.formData.start_date"
            label="Start Date"
            variant="outlined"
            type="date"
            :rules="startDateRules"
            :error-messages="getFieldError('start_date')"
            required
          )
        
        // End Date Field
        v-col(cols="12" md="6")
          v-text-field(
            v-model="editCampaignStore.formData.end_date"
            label="End Date"
            variant="outlined"
            type="date"
            :rules="endDateRules"
            :error-messages="getFieldError('end_date')"
            required
          )
      
      // Logo Upload Section
      v-row
        v-col(cols="12")
          v-card(variant="outlined" class="pa-4")
            v-card-subtitle.mb-3
              v-icon.me-2 mdi-image
              | Campaign Logo
            
            v-row(align="center")
              v-col(cols="12" md="6")
                v-file-input(
                  v-model="logoFileArray"
                  label="Upload Logo"
                  variant="outlined"
                  accept="image/*"
                  :rules="logoRules"
                  :error-messages="editCampaignStore.logoError"
                  :loading="editCampaignStore.uploadingLogo"
                  prepend-icon="mdi-camera"
                  @update:model-value="handleLogoUpload"
                  clearable
                )
              
              v-col(cols="12" md="6")
                .logo-preview(v-if="logoPreview")
                  v-avatar(size="120")
                    v-img(
                      :src="logoPreview"
                      alt="Logo Preview"
                      cover
                    )
                  v-btn(
                    size="small"
                    color="error"
                    variant="text"
                    @click="removeLogo"
                    class="mt-2"
                  )
                    v-icon mdi-delete
                    | Remove
      
      // Media Upload Section
      v-row
        v-col(cols="12")
          v-card(variant="outlined" class="pa-4")
            v-card-subtitle.mb-3
              v-icon.me-2 mdi-file-multiple
              | Campaign Media
            
            v-file-input(
              v-model="mediaFileArray"
              label="Upload Media Files"
              variant="outlined"
              accept="image/*,video/*"
              :rules="mediaRules"
              :error-messages="editCampaignStore.mediaError"
              :loading="editCampaignStore.uploadingMedia"
              prepend-icon="mdi-attachment"
              multiple
              @update:model-value="handleMediaUpload"
              clearable
            )
            
            // Media Upload Progress
            v-row(v-if="editCampaignStore.uploadingMedia" class="mt-2")
              v-col(cols="12")
                .text-caption.mb-2 Uploading media files...
                v-progress-linear(
                  :model-value="editCampaignStore.totalUploadProgress"
                  color="primary"
                  height="6"
                  rounded
                )
            
            // Uploaded Media Preview
            v-row(v-if="editCampaignStore.uploadedMedia.length > 0" class="mt-4")
              v-col(cols="12")
                .text-subtitle-2.mb-2 Uploaded Media:
                v-row
                  v-col(
                    v-for="(media, index) in editCampaignStore.uploadedMedia"
                    :key="media.id"
                    cols="6"
                    sm="4"
                    md="3"
                  )
                    v-card(elevation="1")
                      v-img(
                        v-if="media.mime_type.startsWith('image/')"
                        :src="media.url"
                        :alt="media.name"
                        aspect-ratio="1"
                        cover
                      )
                      v-card-text(
                        v-else
                        class="text-center pa-4"
                      )
                        v-icon(size="40" color="grey") mdi-file
                        .text-caption.mt-2 {{ media.name }}
                      
                      v-card-actions
                        v-btn(
                          size="small"
                          color="error"
                          variant="text"
                          @click="removeMedia(index)"
                        )
                          v-icon mdi-delete
                          | Remove
      
      // Error Display
      v-row(v-if="editCampaignStore.campaignError")
        v-col(cols="12")
          v-alert(
            type="error"
            variant="tonal"
            :text="editCampaignStore.campaignError"
            closable
            @click:close="editCampaignStore.clearErrors"
          )
      
      // Action Buttons
      v-card-actions.justify-end.pt-6
        v-btn(
          variant="outlined"
          @click="handleReset"
          :disabled="editCampaignStore.creatingCampaign"
        )
          v-icon.me-1 mdi-refresh
          | Reset
        
        v-btn(
          color="primary"
          variant="elevated"
          type="submit"
          :loading="editCampaignStore.creatingCampaign"
          :disabled="!editCampaignStore.isFormValid || editCampaignStore.isUploading"
        )
          v-icon.me-1 {{ isEditing ? 'mdi-content-save' : 'mdi-plus' }}
          | {{ isEditing ? 'Update Campaign' : 'Create Campaign' }}
</template>

<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-unused-vars */
import { ref, computed, watch, onMounted } from 'vue'
import { useEditCampaignStore } from '~/stores/editCampaign'
import type { Campaign, CreateCampaignResponse } from '~/types/campaigns'

// Props
interface Props {
  campaign?: Campaign | null
  mode?: 'create' | 'edit'
}

const props = withDefaults(defineProps<Props>(), {
  campaign: null,
  mode: 'create'
})

// Emits
const emit = defineEmits<{
  'success': [campaign: CreateCampaignResponse]
  'error': [error: string]
  'cancel': []
}>()

// Store
const editCampaignStore = useEditCampaignStore()

// Form refs
const form = ref()
const isFormValid = ref(false)

// File input arrays (v-file-input expects arrays)
const logoFileArray = ref<File[]>([])
const mediaFileArray = ref<File[]>([])

// Computed properties
const isEditing = computed(() => props.mode === 'edit' && props.campaign !== null)

const goalAmountDisplay = computed({
  get: () => editCampaignStore.formData.goal_amount ? editCampaignStore.formData.goal_amount / 100 : 0,
  set: (value: number) => {
    editCampaignStore.setFormData({
      ...editCampaignStore.formData,
      goal_amount: Math.round((value || 0) * 100)
    })
  }
})

const currentAmountDisplay = computed(() => {
  return editCampaignStore.formData.current_amount ? editCampaignStore.formData.current_amount / 100 : 0
})

const logoPreview = computed(() => {
  if (editCampaignStore.uploadedLogo) {
    return editCampaignStore.uploadedLogo.url
  }
  if (editCampaignStore.logoFile) {
    return URL.createObjectURL(editCampaignStore.logoFile)
  }
  if (isEditing.value && props.campaign?.logo) {
    return props.campaign.logo
  }
  return null
})

// Validation rules
const titleRules = [
  (v: string) => !!v || 'Title is required',
  (v: string) => (v && v.length <= 255) || 'Title must be less than 255 characters'
]

const descriptionRules = [
  (v: string) => !!v || 'Description is required',
  (v: string) => (v && v.length <= 2000) || 'Description must be less than 2000 characters'
]

const goalAmountRules = [
  (v: number) => !!v || 'Goal amount is required',
  (v: number) => (v && v >= 1) || 'Goal amount must be at least $1'
]

const startDateRules = [
  (v: string) => !!v || 'Start date is required',
  (v: string) => {
    if (!v) return true
    const today = new Date().toISOString().split('T')[0]
    return v >= today || 'Start date cannot be in the past'
  }
]

const endDateRules = [
  (v: string) => !!v || 'End date is required',
  (v: string) => {
    if (!v || !editCampaignStore.formData.start_date) return true
    return v >= editCampaignStore.formData.start_date || 'End date must be after start date'
  }
]

const logoRules = [
  (files: File[]) => {
    if (!files || files.length === 0) return true
    console.log("Logo files: ", files)
    const file = files
    return file.size < 5 * 1024 * 1024 || 'Logo file size must be less than 5MB'
  }
]

const mediaRules = [
  (files: File[]) => {
    if (!files || files.length === 0) return true
    for (const file of files) {
      if (file.size >= 10 * 1024 * 1024) {
        return 'Each media file must be less than 10MB'
      }
    }
    return true
  }
]

// Methods
const getFieldError = (fieldName: string): string[] => {
  return editCampaignStore.validationErrors[fieldName] || []
}

const updateGoalAmount = (value: number) => {
  editCampaignStore.setFormData({
    ...editCampaignStore.formData,
    goal_amount: Math.round((value || 0) * 100)
  })
}

const handleLogoUpload = (files: File[] | null) => {
  if (files && files.length > 0) {
    editCampaignStore.setLogoFile(files[0])
  } else {
    editCampaignStore.setLogoFile(null)
  }
}

const handleMediaUpload = (files: File[] | null) => {
  if (files && files.length > 0) {
    editCampaignStore.addMediaFiles(files)
  }
}

const removeLogo = () => {
  editCampaignStore.setLogoFile(null)
  logoFileArray.value = []
}

const removeMedia = (index: number) => {
  editCampaignStore.removeMediaFile(index)
}

const handleSubmit = async () => {
  if (!isFormValid.value) return

  try {
    let result
    if (isEditing.value) {
      // For edit mode, we would need an update method in the store
      // For now, using createCampaignWithMedia
      result = await editCampaignStore.createCampaignWithMedia()
    } else {
      result = await editCampaignStore.createCampaignWithMedia()
    }
    
    emit('success', result)
  } catch (error) {
    console.error('Error submitting campaign:', error)
    emit('error', error instanceof Error ? error.message : 'Failed to submit campaign')
  }
}

const handleReset = () => {
  editCampaignStore.reset()
  logoFileArray.value = []
  mediaFileArray.value = []
  if (form.value) {
    form.value.resetValidation()
  }
}

// Initialize form data when editing
onMounted(() => {
  if (isEditing.value && props.campaign) {
    editCampaignStore.setFormData({
      title: props.campaign.title,
      description: props.campaign.description,
      goal_amount: props.campaign.goal_amount,
      current_amount: props.campaign.current_amount,
      start_date: props.campaign.start_date,
      end_date: props.campaign.end_date,
      status: props.campaign.status
    })
  } else {
    // Reset store for create mode
    editCampaignStore.reset()
  }
})

// Clear errors when form data changes
watch(() => editCampaignStore.formData, () => {
  editCampaignStore.clearErrors()
}, { deep: true })
</script>

<style scoped>
.campaign-edit-form {
  max-width: 800px;
  margin: 0 auto;
}

.logo-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.v-file-input {
  margin-bottom: 16px;
}

.text-caption {
  font-size: 0.75rem;
  opacity: 0.7;
}
</style>