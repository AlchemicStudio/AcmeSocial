<template lang="pug">
div
  AdminUsersManager
</template>

<script setup lang="ts">
import AdminUsersManager from '~/components/admin/UsersManager.vue'

definePageMeta({
  middleware: ['sanctum:auth'],
  layout: 'administration',
});
</script>
