<template lang="pug">
v-app-bar(app color="white" elevation="1" height="64" hide)
  // Left side - Logo and site name
  v-app-bar-nav-icon(variant="text" to="/")
    v-img(
      src="/img/logo.png"
      alt="Logo"
      width="32"
      height="32"
    )
  
  v-app-bar-title.ml-3
    span.text-h6.font-weight-medium {{ $t('app.name') }}
  
  v-spacer
  
  // Right side - Navigation buttons
  template(v-if="isAuthenticated && user")
    // Create Campaign Button
    v-btn(
      variant="elevated"
      color="primary"
      prepend-icon="mdi-plus"
      class="mr-2"
      @click="createCampaign"
    ) {{ $t('topbar.createCampaign') }}
    
    // Profile Button
    v-btn(
      variant="text"
      icon="mdi-account-circle"
      class="mr-2"
      @click="goToProfile"
    )
      v-icon mdi-account-circle

    // Logout button
    v-btn(
      variant="text"
      icon="mdi-logout"
      color="secondary"
      @click="useSanctumAuth().logout()"
    )

    
    // Admin Button (only if user is admin)
    v-btn(
      v-if="isAdmin"
      variant="text"
      icon="mdi-cog"
      color="secondary"
      @click="goToAdmin"
    )
      v-icon mdi-cog
  
  // Login Button (if not authenticated)
  template(v-else)
    v-btn(
      v-if="route.path !== '/login'"
      variant="elevated"
      color="primary"
      prepend-icon="mdi-login"
      @click="goToLogin"
    ) {{ $t('topbar.login') }}
</template>

<script lang="ts" setup>
const { user, isAuthenticated } = useSanctumAuth()
const route = useRoute()

// Computed properties
const isAdmin = computed(() => {
  return user.value?.is_admin === 1
})

// Methods
const createCampaign = () => {
  navigateTo('/campaigns/create')
}

const goToProfile = () => {
  navigateTo('/profile')
}

const goToAdmin = () => {
  navigateTo('/admin')
}

const goToLogin = () => {
  navigateTo('/login')
}
</script>

<style scoped>
.v-app-bar {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.v-app-bar-title span {
  color: rgb(var(--v-theme-primary));
}

.v-btn {
  text-transform: none;
  font-weight: 500;
}
</style>