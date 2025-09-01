<template lang="pug">
div
  v-card
    v-card-title
      | Users Management
      v-spacer
      v-btn(
        @click="addUserDialog = true"
        color="primary"
        variant="flat"
        prepend-icon="mdi-plus"
        class="mr-2"
      )
        | Add User
      v-btn(
        @click="refreshUsers"
        :loading="isLoading"
        variant="outlined"
        prepend-icon="mdi-refresh"
      )
        | Refresh
    
    v-card-text
      v-table(hover)
        thead
          tr
            th User Name
            th Email
            th Actions
        
        tbody
          tr(v-if="isLoading && users.length === 0")
            td(colspan="3" class="text-center")
              v-progress-circular(indeterminate)
              span.ml-2 Loading users...
          
          tr(v-else-if="users.length === 0")
            td(colspan="3" class="text-center text-grey")
              | No users found
          
          tr(v-for="user in users" :key="user.id")
            td
              div
                .font-weight-bold {{ user.name }}
                .text-caption.text-grey {{ user.role || 'User' }}
            
            td
              div
                .font-weight-medium {{ user.email }}
                .text-caption.text-grey(v-if="user.email_verified_at") Verified
                .text-caption.text-warning(v-else) Not verified
            
            td
              v-btn(
                @click="handlePermissions(user.id)"
                color="primary"
                variant="outlined"
                size="small"
                prepend-icon="mdi-account-key"
                class="mr-2"
              ) Permissions
              
              v-chip(
                @click="handleDelete(user.id)"
                color="error"
                variant="outlined"
                size="small"
                clickable
              )
                v-icon(start) mdi-delete
                | Delete

  // Add User Dialog
  v-dialog(v-model="addUserDialog" max-width="600px")
    AdminUserForm(
      @close="addUserDialog = false"
      @user-created="handleUserCreated"
    )

  // User Permissions Dialog
  v-dialog(v-model="permissionsDialog" max-width="900px")
    AdminUserPermissionForm(
      v-if="permissionsUserId"
      :user-id="permissionsUserId"
      @close="permissionsDialog = false"
    )

  // Delete Confirmation Dialog
  v-dialog(v-model="deleteDialog" max-width="400px")
    v-card
      v-card-title Delete User
      v-card-text
        p Are you sure you want to permanently delete this user? This action cannot be undone.
      v-card-actions
        v-spacer
        v-btn(@click="deleteDialog = false" variant="text") Cancel
        v-btn(
          @click="confirmDelete"
          :loading="isProcessing"
          color="error"
          variant="flat"
        ) Delete
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAdminStore } from '~/stores/admin'
import type { UUID } from '~/types/common'

// Store
const adminStore = useAdminStore()

// Reactive data
const addUserDialog = ref(false)
const deleteDialog = ref(false)
const permissionsDialog = ref(false)
const selectedUserId = ref<UUID | null>(null)
const permissionsUserId = ref<UUID | null>(null)
const isProcessing = ref(false)

// Computed properties
const users = computed(() => adminStore.users || [])
const isLoading = computed(() => adminStore.isLoading)

// Methods
const refreshUsers = async () => {
  try {
    await adminStore.fetchUsers({})
  } catch (error) {
    console.error('Failed to fetch users:', error)
    // In a real app, you'd show a toast notification here
  }
}

const handlePermissions = (userId: UUID) => {
  permissionsUserId.value = userId
  permissionsDialog.value = true
}

const handleDelete = (userId: UUID) => {
  selectedUserId.value = userId
  deleteDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedUserId.value) {
    return
  }
  
  try {
    isProcessing.value = true
    await adminStore.deleteUser(selectedUserId.value)
    await refreshUsers() // Refresh the list
    deleteDialog.value = false
    // Show success message
  } catch (error) {
    console.error('Failed to delete user:', error)
    // Show error message
  } finally {
    isProcessing.value = false
  }
}

const handleUserCreated = async () => {
  addUserDialog.value = false
  await refreshUsers() // Refresh the list to show the new user
}

// Initialize
onMounted(() => {
  refreshUsers()
})
</script>

<style scoped>
.ml-2 {
  margin-left: 8px;
}

.mr-2 {
  margin-right: 8px;
}
</style>