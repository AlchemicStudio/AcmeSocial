<template lang="pug">
div
  v-card
    v-card-title
      | {{ t('components.admin.donations.title') }}
      v-spacer
      v-btn(
        @click="refreshDonations"
        :loading="isLoading"
        variant="outlined"
        prepend-icon="mdi-refresh"
      ) {{ t('common.refresh') }}

    v-card-text
      v-table(hover)
        thead
          tr
            th {{ t('components.admin.donations.headers.campaignName') }}
            th {{ t('components.admin.donations.headers.userName') }}
            th {{ t('components.admin.donations.headers.amount') }}
            th {{ t('components.admin.donations.headers.creationDate') }}
            th {{ t('components.admin.donations.headers.actions') }}
        tbody
          tr(v-if="isLoading && donations.length === 0")
            td(colspan="5" class="text-center")
              v-progress-circular(indeterminate)
              span.ml-2 {{ t('components.admin.donations.loading') }}

          tr(v-else-if="donations.length === 0")
            td(colspan="5" class="text-center text-grey")
              | {{ t('components.admin.donations.empty') }}

          tr(v-for="donation in donations" :key="donation.id")
            td
              div
                .font-weight-bold {{ donation.campaign?.title || '-' }}
            td
              div
                .font-weight-medium {{ donation.donor?.name || '-' }}
            td
              div
                .font-weight-bold {{ formatAmount(donation.amount, donation.currency) }}
            td
              div {{ formatDate(donation.created_at) }}
            td
              v-chip(
                @click="handleRefund(donation.id)"
                color="warning"
                variant="outlined"
                size="small"
                class="mr-1"
                clickable
              )
                v-icon(start) mdi-cash-refund
                | {{ t('components.admin.donations.actions.refund') }}

              v-chip(
                @click="handleDelete(donation.id)"
                color="error"
                variant="outlined"
                size="small"
                clickable
              )
                v-icon(start) mdi-delete
                | {{ t('components.admin.donations.actions.delete') }}

  v-divider
  .d-flex.align-center.justify-end.mt-4
    v-btn(
      :disabled="!hasPrev || isLoading"
      @click="prevPage"
      variant="outlined"
      class="mr-2"
    ) {{ t('common.previous') }}
    span.mx-2 {{ pageInfo }}
    v-btn(
      :disabled="!hasNext || isLoading"
      @click="nextPage"
      variant="outlined"
    ) {{ t('common.next') }}

  // Refund Confirmation Dialog
  v-dialog(v-model="refundDialog" max-width="420px")
    v-card
      v-card-title {{ t('components.admin.donations.dialogs.refundTitle') }}
      v-card-text {{ t('components.admin.donations.dialogs.refundMessage') }}
      v-card-actions
        v-spacer
        v-btn(@click="refundDialog = false" variant="text") {{ t('common.cancel') }}
        v-btn(@click="confirmRefund" :loading="isProcessing" color="warning" variant="flat") {{ t('components.admin.donations.actions.refund') }}

  // Delete Confirmation Dialog
  v-dialog(v-model="deleteDialog" max-width="420px")
    v-card
      v-card-title {{ t('components.admin.donations.dialogs.deleteTitle') }}
      v-card-text {{ t('components.admin.donations.dialogs.deleteMessage') }}
      v-card-actions
        v-spacer
        v-btn(@click="deleteDialog = false" variant="text") {{ t('common.cancel') }}
        v-btn(@click="confirmDelete" :loading="isProcessing" color="error" variant="flat") {{ t('components.admin.donations.actions.delete') }}
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAdminStore } from '~/stores/admin'
import type { UUID } from '~/types/common'

const { t } = useI18n()
const adminStore = useAdminStore()

// Reactive state
const refundDialog = ref(false)
const deleteDialog = ref(false)
const selectedDonationId = ref<UUID | null>(null)
const isProcessing = ref(false)

// Computed
const donations = computed(() => adminStore.donations || [])
const isLoading = computed(() => adminStore.donationsLoading)
const pagination = computed(() => adminStore.donationsPagination)
const currentPage = computed(() => pagination.value?.current_page || 1)
const lastPage = computed(() => pagination.value?.last_page || 1)
const hasPrev = computed(() => currentPage.value > 1)
const hasNext = computed(() => currentPage.value < lastPage.value)
const pageInfo = computed(() => `${currentPage.value} / ${lastPage.value}`)

// Methods
const refreshDonations = async () => {
  await adminStore.fetchDonations({ page: currentPage.value })
}

const nextPage = async () => {
  if (hasNext.value) {
    await adminStore.fetchDonations({ page: currentPage.value + 1 })
  }
}

const prevPage = async () => {
  if (hasPrev.value) {
    await adminStore.fetchDonations({ page: currentPage.value - 1 })
  }
}

const handleRefund = (donationId: UUID) => {
  selectedDonationId.value = donationId
  refundDialog.value = true
}

const confirmRefund = async () => {
  if (!selectedDonationId.value) return
  try {
    isProcessing.value = true
    await adminStore.refundDonation(selectedDonationId.value)
    refundDialog.value = false
    await refreshDonations()
  } catch (err) {
    console.error('Refund failed', err)
  } finally {
    isProcessing.value = false
  }
}

const handleDelete = (donationId: UUID) => {
  selectedDonationId.value = donationId
  deleteDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedDonationId.value) return
  try {
    isProcessing.value = true
    await adminStore.deleteDonation(selectedDonationId.value)
    deleteDialog.value = false
    await refreshDonations()
  } catch (err) {
    console.error('Delete failed', err)
  } finally {
    isProcessing.value = false
  }
}

function formatAmount(amount: number, currency?: string) {
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: currency || 'USD' }).format(amount)
  } catch {
    return `${amount} ${currency || ''}`.trim()
  }
}

function formatDate(dateStr: string) {
  const d = new Date(dateStr)
  return isNaN(d.getTime()) ? dateStr : d.toLocaleString()
}

onMounted(() => {
  refreshDonations()
})
</script>

<style scoped>
.ml-2 { margin-left: 8px; }
.mr-1 { margin-right: 4px; }
.mr-2 { margin-right: 8px; }
.mt-4 { margin-top: 16px; }
</style>
