<template lang="pug">
.v-container
  // Profile header
  v-row
    v-col(cols="12")
      v-card(elevation="2")
        v-card-title
          v-icon.me-2(color="primary") mdi-account-circle
          | My Profile
        v-card-text
          v-row.align-center
            v-col(cols="12" md="6")
              .text-h6 {{ user?.name || '—' }}
              .text-body-2.text-grey-darken-1 {{ user?.email || '' }}
            v-col(cols="12" md="6" class="text-right")
              v-chip(color="primary" variant="tonal" v-if="isAdmin")
                v-icon(size="16" class="mr-1") mdi-shield-check
                | Admin

  // Recent Donations
  v-row
    v-col(cols="12")
      v-card(elevation="2")
        v-card-title
          v-icon.me-2(color="primary") mdi-heart
          | Recent Donations
          v-spacer
          v-btn(size="small" variant="text" @click="refreshDonations" :loading="donationStore.loading")
            v-icon(size="18" class="mr-1") mdi-refresh
            | Refresh
        v-card-text
          // Loading / Error / Empty states
          v-progress-linear(v-if="donationStore.loading" indeterminate color="primary")
          v-alert(type="error" variant="tonal" v-else-if="donationStore.error" :text="donationStore.error")
          template(v-else)
            template(v-if="recentDonations.length === 0")
              .text-body-2.text-grey No recent donations yet.
            template(v-else)
              v-list
                v-list-item(v-for="don in recentDonations" :key="don.id")
                  template(#prepend)
                    v-avatar(color="pink-lighten-5")
                      v-icon(color="pink") mdi-heart
                  v-list-item-title
                    | {{ donationCampaignLabel(don) }}
                  v-list-item-subtitle
                    | {{ formatAmount(don.amount) }} · {{ formatDate(don.created_at) }}

  // My Campaigns
  v-row
    v-col(cols="12")
      v-card(elevation="2")
        v-card-title
          v-icon.me-2(color="primary") mdi-bullhorn
          | My Campaigns
          v-spacer
          v-btn(size="small" variant="text" @click="refreshCampaigns" :loading="campaignsStore.loading")
            v-icon(size="18" class="mr-1") mdi-refresh
            | Refresh
        v-card-text
          v-progress-linear(v-if="campaignsStore.loading" indeterminate color="primary")
          v-alert(type="error" variant="tonal" v-else-if="campaignsStore.error" :text="campaignsStore.error")
          template(v-else)
            template(v-if="myCampaigns.length === 0")
              .text-body-2.text-grey You haven't created any campaigns yet.
            template(v-else)
              v-row
                v-col(v-for="c in myCampaigns" :key="c.id" cols="12" md="6" lg="4")
                  campaigns-thumbnail(:campaign="c")
</template>

<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-unused-vars */
import { onMounted, computed, watch } from 'vue'
import { useDonationStore } from '~/stores/donation'
import { useCampaignsStore } from '~/stores/campaigns'
import type { Donation } from '~/stores/donation'
import type { Campaign } from '~/types/campaigns'

const { user, isAuthenticated } = useSanctumAuth()

const donationStore = useDonationStore()
const campaignsStore = useCampaignsStore()

const isAdmin = computed(() => user.value?.is_admin === true || user.value?.is_admin === 1)

const userId = computed(() => user.value?.id as string | undefined)

const recentDonations = computed<Donation[]>(() => donationStore.donations)
const myCampaigns = computed<Campaign[]>(() => campaignsStore.campaigns)

const loadData = async () => {
  if (!userId.value) return
  // Fetch last 5 donations by the user
  try {
    await donationStore.fetchDonations({ donor_id: userId.value, per_page: 5, page: 1 })
  } catch (e) {
    // error handled in store
  }
  // Fetch campaigns created by the user
  try {
    await campaignsStore.fetchCampaigns({ creator_id: userId.value, per_page: 12, page: 1 })
  } catch (e) {
    // error handled in store
  }
}

onMounted(async () => {
  if (userId.value) {
    await loadData()
  } else {
    const stop = watch(userId, async (val) => {
      if (val) {
        await loadData()
        stop()
      }
    })
  }
})

const refreshDonations = async () => {
  await loadData()
}

const refreshCampaigns = async () => {
  await loadData()
}

const formatAmount = (amountInCents: number): string => {
  const amount = amountInCents / 100
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (iso: string): string => {
  const d = new Date(iso)
  return d.toLocaleString()
}

const donationCampaignLabel = (don: Donation) => {
  if (don.campaign?.title) return `Donation to "${don.campaign.title}"`
  return `Donation to campaign ${don.campaign_id}`
}
</script>

<style scoped>
.text-right { text-align: right; }
</style>
