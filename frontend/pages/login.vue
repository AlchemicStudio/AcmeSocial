<template lang="pug">
div
  v-container
    v-row(justify="center")
      v-col(cols="12" sm="8" md="6" lg="4")
        v-row.mb-3
          v-col(cols="12" sm="8" md="6" lg="4")
            v-img.h-80(src="/img/logo.png")
          v-col
            h1 {{$t('pages.login.site_name')}}
            h3 {{$t('pages.login.welcome')}}
        AuthLogin(
          redirect-to="/"
          @login-success="handleLoginSuccess"
          @login-error="handleLoginError"
          @switch-to-register="handleSwitchToRegister"
          @forgot-password="handleForgotPassword"
        )
        
        v-alert(
          v-if="message"
          :type="messageType"
          variant="tonal"
          class="mt-4"
          dismissible
          @click:close="message = ''"
        ) {{ message }}
</template>

<script lang="ts" setup>
// Page meta
definePageMeta({
  layout: 'default'
})

// Reactive data
const message = ref('')
const messageType = ref<'success' | 'error' | 'info'>('info')

// Event handlers
const handleLoginSuccess = () => {
  message.value = 'Login successful! Redirecting...'
  messageType.value = 'success'
}

const handleLoginError = (error: string) => {
  message.value = `Login error: ${error}`
  messageType.value = 'error'
}

const handleSwitchToRegister = () => {
  message.value = 'Switch to register clicked (implement register component)'
  messageType.value = 'info'
}

const handleForgotPassword = () => {
  message.value = 'Forgot password clicked (implement forgot password flow)'
  messageType.value = 'info'
}
</script>

<style scoped>
.v-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
}
</style>