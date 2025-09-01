<template lang="pug">
div.campaign-view
  // Loading State
  v-skeleton-loader(
    v-if="campaignsStore.loading.fetchCampaign"
    type="article"
    class="mb-4"
  )
  
  // Error State
  v-alert(
    v-else-if="campaignsStore.error"
    type="error"
    variant="tonal"
    class="mb-4"
  )
    | {{ campaignsStore.error }}
  
  // Campaign Content
  div(v-else-if="campaign")
    // Campaign Header
    v-card.mb-4(elevation="2")
      // Campaign Logo/Media
      v-img(
        v-if="campaign.logo"
        :src="campaign.logo"
        height="300"
        cover
        class="campaign-hero-image"
      )
        template(#placeholder)
          v-row.fill-height.ma-0.align-center.justify-center
            v-progress-circular(indeterminate color="grey-lighten-5")
      
      v-card-title.text-h3.pa-6
        | {{ campaign.title }}
      
      v-card-subtitle.px-6.pb-2
        v-chip(
          :color="getStatusColor(campaign.status)"
          variant="elevated"
          class="me-2"
        )
          v-icon.me-1(size="small") {{ getStatusIcon(campaign.status) }}
          | {{ campaign.status_label }}
        
        span.text-medium-emphasis Created by {{ campaign.creator.name }}
    
    // Campaign Progress
    v-card.mb-4(elevation="2")
      v-card-title.text-h5.pb-2
        v-icon.me-2(color="success") mdi-target
        | Funding Progress
      
      v-card-text
        // Progress Bar
        v-progress-linear(
          :model-value="progressPercentage"
          height="20"
          color="success"
          bg-color="grey-lighten-3"
          rounded
          class="mb-4"
        )
          template(#default="{ value }")
            strong {{ Math.ceil(value) }}%
        
        // Progress Stats
        v-row.text-center
          v-col(cols="12" sm="4")
            .text-h6.text-success ${{ formatCurrency(campaign.current_amount) }}
            .text-caption.text-medium-emphasis Raised
          
          v-col(cols="12" sm="4")
            .text-h6.text-primary ${{ formatCurrency(campaign.goal_amount) }}
            .text-caption.text-medium-emphasis Goal
          
          v-col(cols="12" sm="4")
            .text-h6.text-info {{ remainingDays }}
            .text-caption.text-medium-emphasis {{ remainingDays === 1 ? 'Day' : 'Days' }} Left
    
    // Campaign Details
    v-card.mb-4(elevation="2")
      v-card-title.text-h5.pb-2
        v-icon.me-2(color="info") mdi-information
        | Campaign Details
      
      v-card-text
        // Description
        .mb-4
          .text-h6.mb-2 About This Campaign
          .text-body-1.campaign-description {{ campaign.description }}
        
        // Campaign Info
        v-row
          v-col(cols="12" sm="6")
            v-list.bg-transparent(density="compact")
              v-list-item
                template(#prepend)
                  v-icon(color="success") mdi-calendar-start
                v-list-item-title Start Date
                v-list-item-subtitle {{ formatDate(campaign.start_date) }}
              
              v-list-item
                template(#prepend)
                  v-icon(color="error") mdi-calendar-end
                v-list-item-title End Date
                v-list-item-subtitle {{ formatDate(campaign.end_date) }}
          
          v-col(cols="12" sm="6")
            v-list.bg-transparent(density="compact")
              v-list-item
                template(#prepend)
                  v-icon(color="primary") mdi-account
                v-list-item-title Creator
                v-list-item-subtitle {{ campaign.creator.name }} ({{ campaign.creator.email }})
              
              v-list-item(v-if="campaign.created_at")
                template(#prepend)
                  v-icon(color="info") mdi-clock
                v-list-item-title Created
                v-list-item-subtitle {{ formatDate(campaign.created_at) }}
    
    // Campaign Media Gallery
    v-card.mb-4(v-if="campaign.medias && campaign.medias.length > 0" elevation="2")
      v-card-title.text-h5.pb-2
        v-icon.me-2(color="purple") mdi-image-multiple
        | Media Gallery
      
      v-card-text
        v-row
          v-col(
            v-for="media in campaign.medias"
            :key="media.id"
            cols="12"
            sm="6"
            md="4"
          )
            v-img(
              :src="media.url"
              height="200"
              cover
              rounded
              class="campaign-media-item"
            )
              template(#placeholder)
                v-row.fill-height.ma-0.align-center.justify-center
                  v-progress-circular(indeterminate color="grey-lighten-5")
    
    // Action Buttons
    v-card.mb-4(elevation="2")
      v-card-actions.pa-6
        v-spacer
        
        // Donate Button (only show if campaign is approved and active)
        v-btn(
          v-if="canDonate"
          color="success"
          size="large"
          variant="elevated"
          @click="openDonationDialog"
          prepend-icon="mdi-heart"
        )
          | Donate!
        
        // Share Button
        v-btn(
          color="primary"
          variant="outlined"
          @click="shareCampaign"
          prepend-icon="mdi-share"
        )
          | Share Campaign

  // Donation Dialog
  v-dialog(
    v-model="showDonationDialog"
    max-width="600"
    persistent
  )
    DonationsCreateForm(
      :campaign-id="uuid"
      @success="handleDonationSuccess"
      @error="handleDonationError"
      @cancel="closeDonationDialog"
    )
</template>

<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-unused-vars */
import { ref, computed, onMounted, watch } from 'vue'
import { useCampaignsStore } from '~/stores/campaigns'
import type { UUID } from '~/types/common'
import { CampaignStatus } from '~/types/common'
import type { Campaign } from '~/types/campaigns'
import type { Donation } from '~/stores/donation'
import DonationsCreateForm from '~/components/donations/CreateForm.vue'

// Props
interface Props {
  uuid: UUID
}

const props = defineProps<Props>()

// Store
const campaignsStore = useCampaignsStore()

// Reactive Data
const showDonationDialog = ref(false)

// Computed Properties
const campaign = computed((): Campaign | undefined => {
  return campaignsStore.campaigns.find(c => c.id === props.uuid)
})

const progressPercentage = computed((): number => {
  if (!campaign.value) return 0
  return Math.min((campaign.value.current_amount / campaign.value.goal_amount) * 100, 100)
})

const remainingDays = computed((): number => {
  if (!campaign.value) return 0
  const endDate = new Date(campaign.value.end_date)
  const today = new Date()
  const diffTime = endDate.getTime() - today.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return Math.max(diffDays, 0)
})

const canDonate = computed((): boolean => {
  if (!campaign.value) return false
  return campaign.value.status === CampaignStatus.APPROVED && remainingDays.value > 0
})

// Methods
const formatCurrency = (amountInCents: number): string => {
  return (amountInCents / 100).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getStatusColor = (status: CampaignStatus): string => {
  switch (status) {
    case CampaignStatus.DRAFT:
      return 'grey'
    case CampaignStatus.PENDING:
      return 'orange'
    case CampaignStatus.APPROVED:
      return 'success'
    case CampaignStatus.REJECTED:
      return 'error'
    case CampaignStatus.COMPLETED:
      return 'blue'
    case CampaignStatus.CANCELLED:
      return 'red'
    default:
      return 'grey'
  }
}

const getStatusIcon = (status: CampaignStatus): string => {
  switch (status) {
    case CampaignStatus.DRAFT:
      return 'mdi-file-document-edit'
    case CampaignStatus.PENDING:
      return 'mdi-clock-outline'
    case CampaignStatus.APPROVED:
      return 'mdi-check-circle'
    case CampaignStatus.REJECTED:
      return 'mdi-close-circle'
    case CampaignStatus.COMPLETED:
      return 'mdi-trophy'
    case CampaignStatus.CANCELLED:
      return 'mdi-cancel'
    default:
      return 'mdi-help-circle'
  }
}

const openDonationDialog = (): void => {
  showDonationDialog.value = true
}

const closeDonationDialog = (): void => {
  showDonationDialog.value = false
}

const handleDonationSuccess = (donation: Donation): void => {
  closeDonationDialog()
  // Refresh campaign data to show updated amounts
  fetchCampaign()
  // Show success message (you might want to emit an event or show a snackbar)
  console.log('Donation successful:', donation)
}

const handleDonationError = (error: string): void => {
  console.error('Donation error:', error)
  // Error handling is done in the CreateForm component
}

const shareCampaign = (): void => {
  if (!campaign.value) return
  
  if (navigator.share) {
    navigator.share({
      title: campaign.value.title,
      text: campaign.value.description,
      url: window.location.href
    }).catch(console.error)
  } else {
    // Fallback: copy URL to clipboard
    navigator.clipboard.writeText(window.location.href).then(() => {
      // You might want to show a snackbar or toast message
      console.log('Campaign URL copied to clipboard')
    }).catch(console.error)
  }
}

const fetchCampaign = async (): Promise<void> => {
  try {
    await campaignsStore.fetchCampaign(props.uuid)
  } catch (error) {
    console.error('Error fetching campaign:', error)
  }
}

// Lifecycle
onMounted(() => {
  fetchCampaign()
})

// Watch for UUID changes
watch(() => props.uuid, (newUuid) => {
  if (newUuid) {
    fetchCampaign()
  }
})
</script>

<style scoped>
.campaign-view {
  max-width: 1200px;
  margin: 0 auto;
}

.campaign-hero-image {
  border-radius: 8px 8px 0 0;
}

.campaign-description {
  line-height: 1.6;
  white-space: pre-line;
}

.campaign-media-item {
  transition: transform 0.2s ease-in-out;
}

.campaign-media-item:hover {
  transform: scale(1.02);
}

@media (max-width: 600px) {
  .campaign-view {
    padding: 0 8px;
  }
}
</style>