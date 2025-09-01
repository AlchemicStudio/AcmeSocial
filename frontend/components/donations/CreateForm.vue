<template lang="pug">
v-card.donation-create-form(elevation="2")
  v-card-title
    v-icon.me-2(color="primary") mdi-heart
    | Make a Donation
  
  v-card-text
    v-form(ref="form" v-model="isFormValid" @submit.prevent="handleSubmit")
      v-row
        // Amount Field
        v-col(cols="12")
          v-text-field(
            v-model.number="amountDisplay"
            label="Donation Amount ($)"
            variant="outlined"
            type="number"
            :rules="amountRules"
            :error-messages="getFieldError('amount')"
            required
            prefix="$"
            min="1"
            step="0.01"
            placeholder="Enter donation amount"
            @update:model-value="updateAmount"
          )
        
        // Message Field (Optional)
        v-col(cols="12")
          v-textarea(
            v-model="formData.message"
            label="Message (Optional)"
            variant="outlined"
            :rules="messageRules"
            :error-messages="getFieldError('message')"
            rows="3"
            counter="500"
            clearable
            placeholder="Add a message to your donation"
          )
        
        // Visibility Selection
        v-col(cols="12")
          v-select(
            v-model="formData.visibility"
            label="Donation Visibility"
            variant="outlined"
            :items="visibilityOptions"
            item-title="label"
            item-value="value"
            :rules="visibilityRules"
            :error-messages="getFieldError('visibility')"
            required
          )
      
      // Error Display
      v-row(v-if="donationStore.error")
        v-col(cols="12")
          v-alert(
            type="error"
            variant="tonal"
            :text="donationStore.error"
            closable
            @click:close="donationStore.error = null"
          )
  
  v-card-actions
    v-spacer
    v-btn(
      color="grey"
      variant="text"
      @click="handleCancel"
    )
      v-icon.me-1 mdi-close
      | Cancel
    
    v-btn(
      color="primary"
      variant="elevated"
      type="submit"
      :loading="donationStore.makingDonation"
      :disabled="!isFormValid"
      @click="handleSubmit"
    )
      v-icon.me-1 mdi-heart
      | Donate Now
</template>

<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-unused-vars */
import { ref, computed, watch, onMounted } from 'vue'
import { useDonationStore } from '~/stores/donation'
import { DonationVisibility } from '~/types/common'
import type { UUID, CampaignDonationRequest, Donation } from '~/stores/donation'

// Props
interface Props {
  campaignId: UUID
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  'success': [donation: Donation]
  'error': [error: string]
  'cancel': []
}>()

// Store
const donationStore = useDonationStore()

// Form refs
const form = ref()
const isFormValid = ref(false)

// Form data
const formData = ref<CampaignDonationRequest>({
  amount: 0,
  message: '',
  visibility: DonationVisibility.PUBLIC
})

// Amount display for handling cents conversion
const amountDisplay = computed({
  get: () => formData.value.amount ? formData.value.amount / 100 : 0,
  set: (value: number) => {
    formData.value.amount = Math.round((value || 0) * 100)
  }
})

// Visibility options
const visibilityOptions = [
  { label: 'Public - Show my name', value: DonationVisibility.PUBLIC },
  { label: 'Anonymous - Hide my name', value: DonationVisibility.ANONYMOUS }
]

// Validation rules
const amountRules = [
  (v: number) => !!v || 'Donation amount is required',
  (v: number) => (v && v >= 1) || 'Donation amount must be at least $1.00',
  (v: number) => (v && v <= 100000) || 'Donation amount cannot exceed $100,000'
]

const messageRules = [
  (v: string) => !v || v.length <= 500 || 'Message must be less than 500 characters'
]

const visibilityRules = [
  (v: DonationVisibility) => v !== undefined && v !== null || 'Please select visibility preference'
]

// Helper function to get field errors
const getFieldError = (field: string): string[] => {
  // In a real implementation, this would parse validation errors from the API
  return []
}

// Update amount handler
const updateAmount = (value: number) => {
  formData.value.amount = Math.round((value || 0) * 100)
}

// Form submission handler
const handleSubmit = async () => {
  if (!form.value) return
  
  const isValid = await form.value.validate()
  if (!isValid.valid) return
  
  try {
    const donation = await donationStore.makeCampaignDonation(props.campaignId, {
      amount: formData.value.amount,
      message: formData.value.message || undefined,
      visibility: formData.value.visibility
    })
    
    emit('success', donation)
    
    // Reset form
    formData.value = {
      amount: 0,
      message: '',
      visibility: DonationVisibility.PUBLIC
    }
    amountDisplay.value = 0
    
    if (form.value) {
      form.value.reset()
    }
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Failed to create donation'
    emit('error', errorMessage)
  }
}

// Cancel handler
const handleCancel = () => {
  // Reset form
  formData.value = {
    amount: 0,
    message: '',
    visibility: DonationVisibility.PUBLIC
  }
  amountDisplay.value = 0
  
  if (form.value) {
    form.value.reset()
  }
  
  donationStore.error = null
  emit('cancel')
}

// Clear errors when form data changes
watch(formData, () => {
  donationStore.error = null
}, { deep: true })

// Initialize component
onMounted(() => {
  donationStore.error = null
})
</script>

<style scoped>
.donation-create-form {
  max-width: 600px;
  margin: 0 auto;
}
</style>