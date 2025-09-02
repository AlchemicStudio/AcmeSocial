<template lang="pug">
div
  AdminDonationManager
</template>

<script setup lang="ts">
import AdminDonationManager from '~/components/admin/DonationManager.vue'

definePageMeta({
  middleware: ['sanctum:auth'],
  layout: 'administration',
});
</script>
