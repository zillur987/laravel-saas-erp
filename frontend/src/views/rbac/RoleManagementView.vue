<script setup>
import { ref, onMounted } from 'vue'
import { roleService } from '@/services/api/role.service'
import { usePermissions } from '@/composables/usePermissions'
import RoleFormModal from '@/components/rbac/RoleFormModal.vue'

const { can } = usePermissions()

const roles = ref([])
const isLoading = ref(false)
const search = ref('')
const showModal = ref(false)
const editingRole = ref(null)
const errorMessage = ref('')

async function loadRoles() {
  isLoading.value = true
  errorMessage.value = ''
  try {
    roles.value = await roleService.list(search.value)
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? 'Failed to load roles.'
  } finally {
    isLoading.value = false
  }
}

function openCreateModal() {
  editingRole.value = null
  showModal.value = true
}

function openEditModal(role) {
  editingRole.value = role
  showModal.value = true
}

async function handleDelete(role) {
  if (!confirm(`Delete role "${role.name}"? This cannot be undone.`)) return

  try {
    await roleService.remove(role.id)
    await loadRoles()
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? 'Failed to delete role.'
  }
}

function handleSaved() {
  showModal.value = false
  loadRoles()
}

onMounted(loadRoles)
</script>

<template>
  <div class="role-management">
    <header class="role-management__header">
      <h1>Roles &amp; Permissions</h1>
      <button v-can="'roles.create'" class="btn btn--primary" @click="openCreateModal">
        + New Role
      </button>
    </header>

    <div class="role-management__search">
      <input
        v-model="search"
        type="text"
        placeholder="Search roles..."
        @keyup.enter="loadRoles"
      />
      <button class="btn" @click="loadRoles">Search</button>
    </div>

    <p v-if="errorMessage" class="alert alert--error">{{ errorMessage }}</p>
    <p v-if="isLoading">Loading roles...</p>

    <table v-else class="role-table">
      <thead>
        <tr>
          <th>Role</th>
          <th>Permissions</th>
          <th>Users</th>
          <th v-if="can('roles.update') || can('roles.delete')">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="role in roles" :key="role.id">
          <td>{{ role.name }}</td>
          <td class="role-table__permissions">
            <span v-for="perm in role.permissions.slice(0, 4)" :key="perm" class="badge">
              {{ perm }}
            </span>
            <span v-if="role.permissions.length > 4" class="badge badge--muted">
              +{{ role.permissions.length - 4 }} more
            </span>
          </td>
          <td>{{ role.users_count }}</td>
          <td>
            <button v-can="'roles.update'" class="btn btn--sm" @click="openEditModal(role)">
              Edit
            </button>
            <button
              v-can="'roles.delete'"
              class="btn btn--sm btn--danger"
              @click="handleDelete(role)"
            >
              Delete
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <RoleFormModal
      v-if="showModal"
      :role="editingRole"
      @close="showModal = false"
      @saved="handleSaved"
    />
  </div>
</template>