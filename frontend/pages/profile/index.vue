<template lang="pug">
v-row
  v-col(cols="12")
    user-profile
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth'],
})

useHead({
  title: 'My Profile',
})
</script>
