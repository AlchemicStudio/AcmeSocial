<template lang="pug">
div
  v-card
    v-card-title
      | Campaigns Management
      v-spacer
      v-btn(
        @click="refreshCampaigns"
        :loading="isLoading"
        variant="outlined"
        prepend-icon="mdi-refresh"
      )
        | Refresh
    
    v-card-text
      v-table(hover)
        thead
          tr
            th Campaign Name
            th User
            th Actions
        
        tbody
          tr(v-if="isLoading && campaigns.length === 0")
            td(colspan="3" class="text-center")
              v-progress-circular(indeterminate)
              span.ml-2 Loading campaigns...
          
          tr(v-else-if="campaigns.length === 0")
            td(colspan="3" class="text-center text-grey")
              | No campaigns found
          
          tr(v-for="campaign in campaigns" :key="campaign.id")
            td
              div
                .font-weight-bold {{ campaign.title }}
                .text-caption.text-grey {{ campaign.status_label }}
            
            td
              div
                .font-weight-medium {{ campaign.creator.name }}
                .text-caption.text-grey {{ campaign.creator.email }}
            
            td
              v-chip(
                v-if="campaign.status === 'pending'"
                @click="handleApprove(campaign.id)"
                color="success"
                variant="outlined"
                size="small"
                class="mr-1"
                clickable
              )
                v-icon(start) mdi-check
                | Approve
              
              v-chip(
                v-if="campaign.status === 'pending'"
                @click="handleReject(campaign.id)"
                color="error"
                variant="outlined"
                size="small"
                class="mr-1"
                clickable
              )
                v-icon(start) mdi-close
                | Reject
              
              v-chip(
                v-if="campaign.status === 'approved'"
                @click="handleCancel(campaign.id)"
                color="warning"
                variant="outlined"
                size="small"
                class="mr-1"
                clickable
              )
                v-icon(start) mdi-cancel
                | Cancel
              
              v-chip(
                @click="handleDelete(campaign.id)"
                color="error"
                variant="outlined"
                size="small"
                clickable
              )
                v-icon(start) mdi-delete
                | Delete

  // Reject Dialog
  v-dialog(v-model="rejectDialog" max-width="500px")
    v-card
      v-card-title Reject Campaign
      v-card-text
        p Are you sure you want to reject this campaign?
        v-textarea(
          v-model="rejectReason"
          label="Rejection Reason"
          placeholder="Please provide a reason for rejection..."
          rows="3"
          required
        )
      v-card-actions
        v-spacer
        v-btn(@click="rejectDialog = false" variant="text") Cancel
        v-btn(
          @click="confirmReject"
          :loading="isProcessing"
          color="error"
          variant="flat"
        ) Reject Campaign

  // Delete Confirmation Dialog
  v-dialog(v-model="deleteDialog" max-width="400px")
    v-card
      v-card-title Delete Campaign
      v-card-text
        p Are you sure you want to permanently delete this campaign? This action cannot be undone.
      v-card-actions
        v-spacer
        v-btn(@click="deleteDialog = false" variant="text") Cancel
        v-btn(
          @click="confirmDelete"
          :loading="isProcessing"
          color="error"
          variant="flat"
        ) Delete

  // Cancel Confirmation Dialog
  v-dialog(v-model="cancelDialog" max-width="400px")
    v-card
      v-card-title Cancel Campaign
      v-card-text
        p Are you sure you want to cancel this campaign?
      v-card-actions
        v-spacer
        v-btn(@click="cancelDialog = false" variant="text") Cancel
        v-btn(
          @click="confirmCancel"
          :loading="isProcessing"
          color="warning"
          variant="flat"
        ) Cancel Campaign
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useCampaignsStore } from '~/stores/campaigns'
import { useAdminStore } from '~/stores/admin'
import type { UUID } from '~/types/common'

// Stores
const campaignsStore = useCampaignsStore()
const adminStore = useAdminStore()

// Reactive data
const rejectDialog = ref(false)
const deleteDialog = ref(false)
const cancelDialog = ref(false)
const rejectReason = ref('')
const selectedCampaignId = ref<UUID | null>(null)
const isProcessing = ref(false)

// Computed properties
const campaigns = computed(() => campaignsStore.campaigns || [])
const isLoading = computed(() => campaignsStore.isLoading || adminStore.isLoading)

// Methods
const refreshCampaigns = async () => {
  try {
    await campaignsStore.fetchCampaigns({})
  } catch (error) {
    console.error('Failed to fetch campaigns:', error)
    // In a real app, you'd show a toast notification here
  }
}

const handleApprove = async (campaignId: UUID) => {
  try {
    isProcessing.value = true
    await adminStore.approveCampaign(campaignId)
    await refreshCampaigns() // Refresh the list
    // Show success message
  } catch (error) {
    console.error('Failed to approve campaign:', error)
    // Show error message
  } finally {
    isProcessing.value = false
  }
}

const handleReject = (campaignId: UUID) => {
  selectedCampaignId.value = campaignId
  rejectReason.value = ''
  rejectDialog.value = true
}

const confirmReject = async () => {
  if (!selectedCampaignId.value || !rejectReason.value.trim()) {
    return
  }
  
  try {
    isProcessing.value = true
    await adminStore.rejectCampaign(selectedCampaignId.value, rejectReason.value.trim())
    await refreshCampaigns() // Refresh the list
    rejectDialog.value = false
    // Show success message
  } catch (error) {
    console.error('Failed to reject campaign:', error)
    // Show error message
  } finally {
    isProcessing.value = false
    selectedCampaignId.value = null
  }
}

const handleCancel = (campaignId: UUID) => {
  selectedCampaignId.value = campaignId
  cancelDialog.value = true
}

const confirmCancel = async () => {
  if (!selectedCampaignId.value) return
  
  try {
    isProcessing.value = true
    // Use the campaigns store to reject with "cancelled" reason
    // Since admin store doesn't have a cancel method, we'll use reject
    await campaignsStore.rejectCampaign(selectedCampaignId.value, { reason: 'Campaign cancelled by administrator' })
    await refreshCampaigns() // Refresh the list
    cancelDialog.value = false
    // Show success message
  } catch (error) {
    console.error('Failed to cancel campaign:', error)
    // Show error message
  } finally {
    isProcessing.value = false
    selectedCampaignId.value = null
  }
}

const handleDelete = (campaignId: UUID) => {
  selectedCampaignId.value = campaignId
  deleteDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedCampaignId.value) return
  
  try {
    isProcessing.value = true
    await campaignsStore.deleteCampaign(selectedCampaignId.value)
    await refreshCampaigns() // Refresh the list
    deleteDialog.value = false
    // Show success message
  } catch (error) {
    console.error('Failed to delete campaign:', error)
    // Show error message
  } finally {
    isProcessing.value = false
    selectedCampaignId.value = null
  }
}

// Lifecycle
onMounted(() => {
  refreshCampaigns()
})
</script>

<style scoped>
.v-chip {
  cursor: pointer;
}

.v-chip:hover {
  opacity: 0.8;
}
</style>