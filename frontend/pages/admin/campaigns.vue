<template lang="pug">
div
  AdminCampaignsManager
</template>

<script setup lang="ts">
import AdminCampaignsManager from '~/components/admin/CampaignsManager.vue'

definePageMeta({
  middleware: ['sanctum:auth'],
  layout: 'administration',
});
</script>
