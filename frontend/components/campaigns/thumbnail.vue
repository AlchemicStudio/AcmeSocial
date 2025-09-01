<template lang="pug">
v-card.campaign-thumbnail(elevation="2" hover :to="'/campaigns/' + campaign.id")
  v-row(no-gutters align="center")
    v-col(cols="auto" class="pa-4")
      v-avatar(
        size="80"
      )
        v-img(
          v-if="campaign.logo"
          :src="campaign.logo"
          :alt="`${campaign.title} logo`"
          cover
        )
        v-icon(
          v-else
          size="40"
          color="grey"
        ) mdi-image-outline
    
    v-col(class="pa-4")
      v-card-title.text-h6.mb-2.line-clamp-2 {{ campaign.title }}
      
      v-card-text.pa-0
        p.text-body-2.text-grey-darken-1.mb-3.line-clamp-3 {{ truncatedDescription }}
        
        v-row.align-center.no-gutters
          v-col(cols="12" sm="6")
            .text-body-2.mb-1
              strong Goal: 
              span.text-success {{ formatAmount(campaign.goal_amount) }}
          
          v-col(cols="12" sm="6")
            .text-body-2.mb-1
              strong Raised: 
              span.text-primary {{ formatAmount(campaign.current_amount) }}
        
        v-progress-linear(
          :model-value="progressPercentage"
          color="primary"
          height="8"
          rounded
          class="mt-3"
        )
          template(#default="{ value }")
            .text-caption.text-center.text-white
              | {{ Math.round(value) }}%
</template>

<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-unused-vars */
import { computed } from 'vue'
import type { Campaign } from '~/types/campaigns'

// Props
interface Props {
  campaign: Campaign
}

const props = defineProps<Props>()

// Computed properties
const truncatedDescription = computed(() => {
  if (props.campaign.description.length <= 100) {
    return props.campaign.description
  }
  return props.campaign.description.substring(0, 100) + '...'
})

const progressPercentage = computed(() => {
  if (props.campaign.goal_amount === 0) return 0
  return Math.min((props.campaign.current_amount / props.campaign.goal_amount) * 100, 100)
})

// Methods
const formatAmount = (amountInCents: number): string => {
  const amount = amountInCents / 100
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(amount)
}
</script>

<style scoped>
.campaign-thumbnail {
  transition: all 0.3s ease;
}

.campaign-thumbnail:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
  max-height: calc(1.4em * 2);
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.5;
  max-height: calc(1.5em * 3);
}

.v-progress-linear {
  border-radius: 4px;
}
</style>