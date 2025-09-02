<template lang="pug">
div
  h1 Admin
  AdminPendingCampaign
</template>

<script setup lang="ts">
import AdminPendingCampaign from '~/components/admin/PendingCampaign.vue'

definePageMeta({
  middleware: ['sanctum:auth'],
  layout: 'administration',
});
</script>
