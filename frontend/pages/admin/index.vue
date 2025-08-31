<template lang="pug">
h1 Admin
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth'],
  layout: 'administration',
});
</script>
