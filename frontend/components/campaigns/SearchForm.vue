<template lang="pug">
v-card.mb-6
  v-card-title
    v-icon.me-2(color="primary") mdi-magnify
    | Search Campaigns
  
  v-card-text
    v-row
      v-col(cols="12" md="8")
        v-text-field(
          v-model="searchQuery"
          label="Search campaigns..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          clearable
          @input="onSearchInput"
          @click:clear="clearSearch"
        )
      
      v-col(cols="12" md="4")
        v-select(
          v-model="selectedPerPage"
          label="Items per page"
          :items="perPageOptions"
          variant="outlined"
          @update:modelValue="onPerPageChange"
        )
    
    v-row.mt-2(v-if="hasActiveFilters")
      v-col(cols="12")
        v-chip-group
          v-chip(
            v-if="searchQuery"
            closable
            @click:close="clearSearch"
            color="primary"
            variant="outlined"
          )
            v-icon.me-1 mdi-magnify
            | Search: "{{ searchQuery }}"
          
          v-chip(
            v-if="selectedPerPage !== defaultPerPage"
            closable
            @click:close="resetPerPage"
            color="secondary"
            variant="outlined"
          )
            v-icon.me-1 mdi-format-list-numbered
            | {{ selectedPerPage }} items per page
</template>

<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-unused-vars */
import { ref, computed, watch, onMounted, readonly } from 'vue'
import { useCampaignsStore } from '~/stores/campaigns'
import type { CampaignQueryParams, Campaign } from '~/types/campaigns'

// Store
const campaignStore = useCampaignsStore()

// Reactive state
const searchQuery = ref('')
const selectedPerPage = ref(15)
const defaultPerPage = 15

// Search debounce timer
let searchTimeout: NodeJS.Timeout | null = null

// Per page options - used in template
const perPageOptions = [
  { title: '10', value: 10 },
  { title: '15', value: 15 },
  { title: '25', value: 25 },
  { title: '50', value: 50 },
  { title: '100', value: 100 }
]

// Computed properties - used in template
const hasActiveFilters = computed(() => {
  return searchQuery.value !== '' || selectedPerPage.value !== defaultPerPage
})

// Initialize with current store values
selectedPerPage.value = campaignStore.perPage || defaultPerPage

// Methods - used in template
const onSearchInput = () => {
  // Clear existing timeout
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  // Set new timeout for debounced search
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

const onPerPageChange = () => {
  applyFilters()
}

const applyFilters = async () => {
  const filters: CampaignQueryParams = {
    per_page: selectedPerPage.value
  }
  
  try {
    await campaignStore.setFilters(filters)
  } catch (error) {
    console.error('Error applying filters:', error)
  }
}

// Computed property for filtered campaigns (for client-side search)
const filteredCampaigns = computed(() => {
  if (!searchQuery.value) {
    return campaignStore.campaigns
  }
  
  const query = searchQuery.value.toLowerCase()
  return campaignStore.campaigns.filter(campaign => 
    campaign.title.toLowerCase().includes(query) ||
    campaign.description.toLowerCase().includes(query) ||
    campaign.creator.name.toLowerCase().includes(query)
  )
})

const clearSearch = () => {
  searchQuery.value = ''
  applyFilters()
}

const resetPerPage = () => {
  selectedPerPage.value = defaultPerPage
  applyFilters()
}

// Watch for store changes to keep component in sync
watch(() => campaignStore.perPage, (newPerPage) => {
  if (newPerPage && newPerPage !== selectedPerPage.value) {
    selectedPerPage.value = newPerPage
  }
})

// Initialize filters on mount
onMounted(() => {
  if (campaignStore.campaigns.length === 0) {
    applyFilters()
  }
})

// Emit events to parent components
const emit = defineEmits<{
  'search-change': [query: string, filteredCampaigns: Campaign[]]
  'per-page-change': [perPage: number]
}>()

// Watch for search changes and emit to parent
watch([searchQuery, filteredCampaigns], ([newQuery, newFilteredCampaigns]) => {
  emit('search-change', newQuery, newFilteredCampaigns)
}, { deep: true })

// Watch for per page changes and emit to parent
watch(selectedPerPage, (newPerPage) => {
  emit('per-page-change', newPerPage)
})

// Expose filtered campaigns for parent components
defineExpose({
  filteredCampaigns,
  searchQuery: readonly(searchQuery),
  selectedPerPage: readonly(selectedPerPage)
})
</script>

<style scoped>
.v-chip-group {
  margin-top: 8px;
}
</style>