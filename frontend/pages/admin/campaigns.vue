<template lang="pug">
h1 Admin Campaigns
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth'],
  layout: 'administration',
});
</script>
