<template lang="pug">
div
  v-card
    v-card-title
      | Pending Campaigns
      v-spacer
      v-btn(
        @click="refresh"
        :loading="adminStore.campaignsLoading"
        variant="outlined"
        prepend-icon="mdi-refresh"
      ) Refresh
    v-card-text
      v-alert(type="error" variant="tonal" v-if="adminStore.campaignsError") {{ adminStore.campaignsError }}
      v-table(hover)
        thead
          tr
            th Campaign
            th Creator
            th Actions
        tbody
          tr(v-if="adminStore.campaignsLoading && pending.length === 0")
            td(colspan="3" class="text-center")
              v-progress-circular(indeterminate)
              span.ml-2 Loading...
          tr(v-else-if="pending.length === 0")
            td(colspan="3" class="text-center text-grey")
              | No pending campaigns
          tr(v-for="campaign in pending" :key="campaign.id")
            td
              div
                .font-weight-bold {{ campaign.title }}
                .text-caption.text-grey {{ campaign.status_label }}
            td
              div
                .font-weight-medium {{ campaign.creator?.name }}
                .text-caption.text-grey {{ campaign.creator?.email }}
            td
              v-chip(
                @click="approve(campaign.id)"
                color="success"
                variant="outlined"
                size="small"
                class="mr-1"
                clickable
              )
                v-icon(start) mdi-check
                | Approve
              v-chip(
                @click="reject(campaign.id)"
                color="error"
                variant="outlined"
                size="small"
                class="mr-1"
                clickable
              )
                v-icon(start) mdi-close
                | Reject
              v-btn(
                @click="openView(campaign.id)"
                variant="outlined"
                size="small"
                prepend-icon="mdi-eye"
              ) View

  v-dialog(v-model="showView" max-width="1000")
    v-card
      v-card-title
        | Campaign Details
        v-spacer
        v-btn(icon="mdi-close" variant="text" @click="showView=false")
      v-card-text
        CampaignsView(v-if="selectedId" :uuid="selectedId")
</template>

<script setup lang="ts">
import { onMounted, computed, ref } from 'vue'
import { useAdminStore } from '~/stores/admin'
import type { UUID } from '~/types/common'
import CampaignsView from '~/components/campaigns/View.vue'

const adminStore = useAdminStore()
const pending = computed(() => adminStore.pendingCampaigns)

const showView = ref(false)
const selectedId = ref<UUID | null>(null)

const refresh = async () => {
  await adminStore.fetchCampaigns({})
}

const approve = async (id: UUID) => {
  try {
    await adminStore.approveCampaign(id)
  } catch (e) {
    console.error('Failed to approve campaign:', e)
  }
}

const reject = async (id: UUID) => {
  const reason = window.prompt('Please provide a reason for rejection:', '') || ''
  try {
    await adminStore.rejectCampaign(id, reason)
  } catch (e) {
    console.error('Failed to reject campaign:', e)
  }
}

const openView = (id: UUID) => {
  selectedId.value = id
  showView.value = true
}

onMounted(async () => {
  if (adminStore.campaigns.length === 0) {
    await refresh()
  }
})
</script>
