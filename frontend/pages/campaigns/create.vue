<template lang="pug">
campaigns-edit-form
</template>

<script setup lang="ts">

definePageMeta({
  middleware: ['sanctum:auth'],
});
</script>