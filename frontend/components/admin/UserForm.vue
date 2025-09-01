<template lang="pug">
v-card
  v-card-title
    | Add New User
    v-spacer
    v-btn(
      @click="$emit('close')"
      icon="mdi-close"
      variant="text"
      size="small"
    )
  
  v-card-text
    v-form(ref="formRef" @submit.prevent="handleSubmit")
      v-text-field(
        v-model="formData.name"
        label="Full Name"
        :rules="nameRules"
        required
        variant="outlined"
        prepend-inner-icon="mdi-account"
      )
      
      v-text-field(
        v-model="formData.email"
        label="Email Address"
        :rules="emailRules"
        required
        variant="outlined"
        type="email"
        prepend-inner-icon="mdi-email"
      )
      
      v-text-field(
        v-model="formData.password"
        label="Password"
        :rules="passwordRules"
        required
        variant="outlined"
        type="password"
        prepend-inner-icon="mdi-lock"
      )
      
      v-text-field(
        v-model="formData.confirm_password"
        label="Confirm Password"
        :rules="confirmPasswordRules"
        required
        variant="outlined"
        type="password"
        prepend-inner-icon="mdi-lock-check"
      )
      
      v-switch(
        v-model="formData.is_admin"
        label="Admin User"
        color="primary"
        inset
        :false-value="0"
        :true-value="1"
      )
  
  v-card-actions
    v-spacer
    v-btn(
      @click="$emit('close')"
      variant="text"
    ) Cancel
    v-btn(
      @click="handleSubmit"
      :loading="isSubmitting"
      color="primary"
      variant="flat"
    ) Create User
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useAdminStore } from '~/stores/admin'
import type { CreateUserRequest } from '~/stores/admin'

// Define emits
const emit = defineEmits<{
  close: []
  userCreated: []
}>()

// Store
const adminStore = useAdminStore()

// Form reference
const formRef = ref()

// Reactive data
const isSubmitting = ref(false)

const formData = reactive<CreateUserRequest>({
  name: '',
  email: '',
  password: '',
  confirm_password: '',
  is_admin: false
})

// Validation rules
const nameRules = [
  (v: string) => !!v || 'Name is required',
  (v: string) => v.length >= 2 || 'Name must be at least 2 characters'
]

const emailRules = [
  (v: string) => !!v || 'Email is required',
  (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid'
]

const passwordRules = [
  (v: string) => !!v || 'Password is required',
  (v: string) => v.length >= 8 || 'Password must be at least 8 characters',
  (v: string) => /(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(v) || 'Password must contain uppercase, lowercase and number'
]

const confirmPasswordRules = [
  (v: string) => !!v || 'Please confirm your password',
  (v: string) => v === formData.password || 'Passwords do not match'
]

// Methods
const handleSubmit = async () => {
  // Validate form
  if (!formRef.value) return
  
  const { valid } = await formRef.value.validate()
  if (!valid) {
    return
  }

  try {
    isSubmitting.value = true
    await adminStore.createUser(formData)
    
    // Reset form
    Object.assign(formData, {
      name: '',
      email: '',
      password: '',
      confirm_password: '',
      is_admin: false
    })
    
    // Emit success
    emit('userCreated')
    
    // Show success message (in a real app, you'd use a toast)
    console.log('User created successfully')
    
  } catch (error) {
    console.error('Failed to create user:', error)
    // Show error message (in a real app, you'd use a toast)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.v-card-title {
  padding-bottom: 8px;
}
</style>