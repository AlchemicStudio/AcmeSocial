<template lang="pug">
v-card.mx-auto(max-width="400" elevation="8")
  v-card-title.text-center.py-6
    h2.text-h4 {{ $t('auth.login.title') }}
  
  v-card-text
    v-form(ref="form" v-model="valid" @submit.prevent="handleLogin")
      v-text-field(
        v-model="credentials.email"
        :label="$t('auth.login.email')"
        :rules="emailRules"
        type="email"
        prepend-inner-icon="mdi-email"
        variant="outlined"
        required
        :disabled="loading"
      )
      
      v-text-field(
        v-model="credentials.password"
        :label="$t('auth.login.password')"
        :rules="passwordRules"
        :type="showPassword ? 'text' : 'password'"
        prepend-inner-icon="mdi-lock"
        :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
        @click:append-inner="showPassword = !showPassword"
        variant="outlined"
        required
        :disabled="loading"
      )
      
      v-checkbox(
        v-model="rememberMe"
        :label="$t('auth.login.rememberMe')"
        :disabled="loading"
      )
      
      v-alert(
        v-if="errorMessage"
        type="error"
        variant="tonal"
        class="mb-4"
        dismissible
        @click:close="errorMessage = ''"
      ) {{ errorMessage }}
      
      v-btn(
        type="submit"
        color="primary"
        size="large"
        block
        :loading="loading"
        :disabled="!valid || loading"
      ) {{ $t('auth.login.submit') }}

</template>

<script lang="ts" setup>
interface LoginCredentials {
  email: string
  password: string
}

interface Props {
  redirectTo?: string
}

const props = withDefaults(defineProps<Props>(), {
  redirectTo: '/'
})

const emit = defineEmits<{
  loginSuccess: []
  loginError: [error: string]
  switchToRegister: []
  forgotPassword: []
}>()

// Composables
const { t } = useI18n()
const { login, user, isAuthenticated } = useSanctumAuth()

// Reactive data
const form = ref()
const valid = ref(false)
const loading = ref(false)
const showPassword = ref(false)
const rememberMe = ref(false)
const errorMessage = ref('')

const credentials = reactive<LoginCredentials>({
  email: '',
  password: ''
})

// Validation rules
const emailRules = computed(() => [
  (v: string) => !!v || t('auth.validation.emailRequired'),
  (v: string) => /.+@.+\..+/.test(v) || t('auth.validation.emailInvalid')
])

const passwordRules = computed(() => [
  (v: string) => !!v || t('auth.validation.passwordRequired'),
  (v: string) => v.length >= 6 || t('auth.validation.passwordMinLength')
])

// Methods
const handleLogin = async () => {
  if (!form.value) return
  
  const { valid: isFormValid } = await form.value.validate()
  if (!isFormValid) return
  
  loading.value = true
  errorMessage.value = ''
  
  try {
    await login({
      email: credentials.email,
      password: credentials.password,
      remember: rememberMe.value
    })
    
    emit('loginSuccess')
    
    // Redirect after successful login
    await navigateTo(props.redirectTo)
    
  } catch (error: unknown) {
    console.error('Login error:', error)
    
    // Handle different types of errors
    const httpError = error as { response?: { status: number }; message?: string }
    if (httpError.response?.status === 422) {
      errorMessage.value = t('auth.errors.invalidCredentials')
    } else if (httpError.response?.status === 429) {
      errorMessage.value = t('auth.errors.tooManyAttempts')
    } else {
      errorMessage.value = httpError.message || t('auth.errors.loginFailed')
    }
    
    emit('loginError', errorMessage.value)
  } finally {
    loading.value = false
  }
}

// Reset form when component mounts
onMounted(() => {
  if (form.value) {
    form.value.reset()
  }
})

// Watch for authentication status changes
watch(isAuthenticated, (newValue) => {
  if (newValue && user.value) {
    emit('loginSuccess')
  }
})
</script>

<style scoped>
.v-card {
  border-radius: 12px;
}

.v-card-title h2 {
  font-weight: 300;
  color: rgb(var(--v-theme-primary));
}

.v-btn {
  text-transform: none;
  font-weight: 500;
}

.v-text-field {
  margin-bottom: 8px;
}
</style>