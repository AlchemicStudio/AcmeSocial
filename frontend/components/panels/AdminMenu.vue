<template lang="pug">
v-navigation-drawer(permanent)
  v-list-item(to="/admin/campaigns") {{$t('components.panels.admin_menu.campaigns')}}
  v-list-item(to="/admin/donations") {{$t('components.panels.admin_menu.donations')}}
  v-list-item(to="/admin/users") {{$t('components.panels.admin_menu.users')}}
</template>

<script setup lang="ts">
</script>