<template lang="pug">
h1 Index
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth'],
});
</script>
