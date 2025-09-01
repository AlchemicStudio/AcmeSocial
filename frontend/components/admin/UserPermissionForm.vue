<template lang="pug">
v-card(max-width="800px")
  v-card-title
    | Manage Permissions for {{ user?.name || 'User' }}
    v-spacer
    v-btn(
      @click="$emit('close')"
      icon
      variant="text"
    )
      v-icon mdi-close

  v-card-text
    // Loading state
    div(v-if="isLoading" class="text-center py-8")
      v-progress-circular(indeterminate)
      .mt-2 Loading permissions...

    // Error state
    v-alert(
      v-if="error"
      type="error"
      variant="outlined"
      class="mb-4"
    ) {{ error }}

    // Current permissions table
    div(v-if="!isLoading")
      .text-h6.mb-4 Current Permissions
      
      v-table(v-if="userPermissions && userPermissions.length > 0" hover)
        thead
          tr
            th Permission Name
            th Description
            th Actions
        
        tbody
          tr(v-for="permission in userPermissions" :key="permission.id")
            td
              .font-weight-bold {{ permission.name }}
            
            td
              .text-body-2.text-grey {{ permission.description || 'No description available' }}
            
            td
              v-btn(
                @click="handleRemovePermission(permission.id)"
                :loading="isRemoving === permission.id"
                color="error"
                variant="outlined"
                size="small"
                prepend-icon="mdi-minus"
              ) Remove

      v-alert(
        v-else
        type="info"
        variant="outlined"
        class="mb-4"
      ) This user has no permissions assigned.

      // Add permission section
      .mt-6
        .text-h6.mb-4 Add Permission
        
        v-row(align="center")
          v-col(cols="8")
            v-select(
              v-model="selectedPermissionId"
              :items="availablePermissions"
              item-value="name"
              item-title="name"
              label="Select Permission"
              variant="outlined"
              :disabled="isAdding"
              clearable
            )
              template(#item="{ props, item }")
                v-list-item(v-bind="props")
                  template(#title)
                    .font-weight-medium {{ item.raw.name }}
                  template(#subtitle)
                    .text-caption {{ item.raw.description }}
          
          v-col(cols="4")
            v-btn(
              @click="handleAddPermission"
              :loading="isAdding"
              :disabled="!selectedPermissionId"
              color="primary"
              variant="flat"
              block
              prepend-icon="mdi-plus"
            ) Add Permission

  v-card-actions
    v-spacer
    v-btn(
      @click="$emit('close')"
      variant="outlined"
    ) Close
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAdminStore } from '~/stores/admin'
import type { UUID } from '~/types/common'

// Props
interface Props {
  userId: UUID
}

const props = defineProps<Props>()

// Emits
defineEmits<{
  close: []
}>()

// Store
const adminStore = useAdminStore()

// Reactive data
const isLoading = ref(false)
const isAdding = ref(false)
const isRemoving = ref<UUID | null>(null)
const selectedPermissionId = ref<UUID | null>(null)
const error = ref<string | null>(null)

// Computed properties
const user = computed(() => 
  adminStore.users?.find(u => u.id === props.userId)
)

const userPermissions = computed(() => 
  adminStore.userPermissions[props.userId]?.permissions || []
)

const allPermissions = computed(() => 
  adminStore.permissions || []
)

const availablePermissions = computed(() => {
  const userPermissionIds = new Set(userPermissions.value.map(p => p.id))
  return allPermissions.value.filter(p => !userPermissionIds.has(p.id))
})

// Methods
const fetchPermissions = async () => {
  try {
    isLoading.value = true
    error.value = null
    
    // Fetch all permissions and user permissions in parallel
    await Promise.all([
      adminStore.fetchAllPermissions(),
      adminStore.fetchUserPermissions(props.userId)
    ])
  } catch (err: unknown) {
    error.value = (err as Error).message || 'Failed to load permissions'
    console.error('Error fetching permissions:', err)
  } finally {
    isLoading.value = false
  }
}

const handleAddPermission = async () => {
  if (!selectedPermissionId.value) return

  try {
    isAdding.value = true
    error.value = null

    await adminStore.assignPermissions(props.userId, [selectedPermissionId.value])
    await adminStore.fetchUserPermissions(props.userId) // Refresh user permissions
    
    selectedPermissionId.value = null
  } catch (err: unknown) {
    error.value = (err as Error).message || 'Failed to add permission'
    console.error('Error adding permission:', err)
  } finally {
    isAdding.value = false
  }
}

const handleRemovePermission = async (permissionId: UUID) => {
  try {
    isRemoving.value = permissionId
    error.value = null

    await adminStore.removePermissions(props.userId, [permissionId])
    await adminStore.fetchUserPermissions(props.userId) // Refresh user permissions
  } catch (err: unknown) {
    error.value = (err as Error).message || 'Failed to remove permission'
    console.error('Error removing permission:', err)
  } finally {
    isRemoving.value = null
  }
}

// Watch for user ID changes
watch(() => props.userId, () => {
  if (props.userId) {
    fetchPermissions()
  }
}, { immediate: true })

// Initialize
onMounted(() => {
  if (props.userId) {
    fetchPermissions()
  }
})
</script>

<style scoped>
.py-8 {
  padding-top: 32px;
  padding-bottom: 32px;
}

.mt-2 {
  margin-top: 8px;
}

.mt-6 {
  margin-top: 24px;
}

.mb-4 {
  margin-bottom: 16px;
}
</style>