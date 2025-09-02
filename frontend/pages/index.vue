<template lang="pug">
  v-row
    v-col
      campaigns-search-form
  v-row
    v-col
      campaigns-thumbnail(
        v-for="campaign in campaignStore.approvedCampaigns"
        :key="campaign.id"
        :campaign="campaign"
      )
</template>

<script setup lang="ts">
import { useCampaignsStore } from '~/stores/campaigns'

definePageMeta({
  middleware: ['sanctum:auth'],
});

const campaignStore = useCampaignsStore()

onMounted(async () => {
  console.log('Mounted')
  await campaignStore.fetchCampaigns({})
})
</script>
