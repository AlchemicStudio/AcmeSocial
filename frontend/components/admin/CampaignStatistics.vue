<template lang="pug">
div
  v-card
    v-card-title
      | {{ $t('components.admin.campaigns.statistics.title') }}
    v-card-text
      div
        // Loading state
        div(v-if="loading" class="d-flex align-center")
          v-progress-circular(indeterminate)
          span.ml-2 {{ $t('components.admin.campaigns.statistics.loading') }}

        // Error state
        v-alert(v-else-if="error" type="error" variant="tonal" density="comfortable")
          | {{ errorMessage }}

        // Empty state
        v-alert(v-else-if="!hasData" type="info" variant="tonal" density="comfortable")
          | {{ $t('components.admin.campaigns.statistics.noData') }}

        // Chart
        ClientOnly(v-else)
          .chart-wrapper
            Line(:data="stats" :options="chartOptions")
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  type ChartData,
  type ChartOptions
} from 'chart.js'
import { useI18n } from 'vue-i18n'
import { useAdminStore } from '~/stores/admin'
import type { UUID } from '~/types/common'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

interface Props {
  campaignId: UUID | string
}

const props = defineProps<Props>()

const admin = useAdminStore()
const { t } = useI18n()
const loading = ref(false)
const error = ref<unknown>(null)
const stats = ref<unknown>(null)

const chartData = computed<ChartData<'line'>>(() => buildChartData(stats.value))

const hasData = computed(() => {
  const d = chartData.value
  return !!d && Array.isArray(d.labels) && d.labels.length > 0 && (
    d.datasets?.some(ds => Array.isArray((ds as { data?: unknown }).data as unknown[]) && ((ds as { data?: unknown[] }).data?.length || 0) > 0)
  )
})

const errorMessage = computed(() => (admin.campaignsError || (error.value as Error)?.message) || t('components.admin.campaigns.statistics.error'))

const chartOptions = computed<ChartOptions<'line'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { position: 'top' },
  },
  scales: {
    x: {
      title: { display: true, text: t('components.admin.campaigns.statistics.axis.date') }
    },
    y: {
      title: { display: true, text: t('components.admin.campaigns.statistics.axis.amount') },
      beginAtZero: true
    }
  }
}))

function buildChartData(raw: unknown): ChartData<'line'> {
  if (!raw || typeof raw !== 'object') return { labels: [], datasets: [] }
  const r: any = raw as any

  // If API already returns chart.js-like data
  if (r.labels && r.datasets) {
    return r as ChartData<'line'>
  }

  // Common shapes normalization
  // 1) timeline: [{date, amount}] or [{label, value}]
  const timeline = r.timeline || r.daily || r.daily_donations || r.data
  if (Array.isArray(timeline)) {
    const labels = timeline.map((p: any) => p.date || p.label || p.day || p.name || '')
    const data = timeline.map((p: any) => Number(p.amount ?? p.value ?? p.count ?? 0))
    return {
      labels,
      datasets: [
        {
          label: t('components.admin.campaigns.statistics.series.raised'),
          data,
          borderColor: '#1976D2',
          backgroundColor: 'rgba(25, 118, 210, 0.2)',
          tension: 0.25,
          pointRadius: 3
        }
      ]
    }
  }

  // 2) keyed object { '2025-01-01': 100, ... }
  if (r.by_date && typeof r.by_date === 'object') {
    const labels = Object.keys(r.by_date)
    const data = labels.map(k => Number(r.by_date[k] ?? 0))
    return {
      labels,
      datasets: [
        {
          label: t('components.admin.campaigns.statistics.series.raised'),
          data,
          borderColor: '#1976D2',
          backgroundColor: 'rgba(25, 118, 210, 0.2)',
          tension: 0.25,
          pointRadius: 3
        }
      ]
    }
  }

  return { labels: [], datasets: [] }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const data = await admin.fetchCampaignStatistics(props.campaignId as UUID)
    console.log('dataaaaa', data)
    stats.value = data.statistics
  } catch (e) {
    error.value = e
  } finally {
    loading.value = false
  }
}

onMounted(load)

watch(
  () => props.campaignId,
  () => {
    load()
  }
)
</script>

<style scoped>
.chart-wrapper { height: 360px; }
</style>
