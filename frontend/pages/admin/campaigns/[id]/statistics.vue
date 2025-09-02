<template lang="pug">
div
  AdminCampaignStatistics(:campaign-id="campaignId")
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

definePageMeta({
  middleware: ['sanctum:auth'],
  layout: 'administration',
})

const route = useRoute()
const campaignId = computed(() => String(route.params.id))
</script>
