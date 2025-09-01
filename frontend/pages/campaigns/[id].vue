<template lang="pug">
div.campaign-page
  CampaignsView(:uuid="campaignId")
</template>

<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-unused-vars */
import { useRoute } from 'vue-router'
import type { UUID } from '~/types/common'
import CampaignsView from '~/components/campaigns/View.vue'

// Get campaign ID from route params
const route = useRoute()
const campaignId = route.params.id as UUID

// Set page meta
definePageMeta({
  layout: 'default'
})
</script>

<style scoped>
.campaign-page {
  padding: 16px;
}

@media (max-width: 600px) {
  .campaign-page {
    padding: 8px;
  }
}
</style>
