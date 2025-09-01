<template lang="pug">
h1 Admin Settings
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth'],
  layout: 'administration',
});
</script>
