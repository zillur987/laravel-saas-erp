<script setup>
import { ref, onMounted } from 'vue'
import http from '@/services/api/http'
import { userAccessService } from '@/services/api/userAccess.service'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps({
  user: { type: Object, required: true }, // { id, name, roles: [] }
})

const emit = defineEmits(['updated'])

const { can } = usePermissions()

const availableRoles = ref([]) // ['admin', 'manager', ...]
const selectedRoles = ref(new Set(props.user.roles ?? []))
const isSaving = ref(false)
const errorMessage = ref('')

async function loadAvailableRoles() {
  const roles = await http.get('/roles').then((res) => res.data.data)
  availableRoles.value = roles.map((r) => r.name)
}

function toggleRole(roleName) {
  if (selectedRoles.value.has(roleName)) {
    selectedRoles.value.delete(roleName)
  } else {
    selectedRoles.value.add(roleName)
  }
}

async function handleSave() {
  isSaving.value = true
  errorMessage.value = ''

  try {
    const updatedUser = await userAccessService.syncRoles(
      props.user.id,
      Array.from(selectedRoles.value)
    )
    emit('updated', updatedUser)
  } catch (error) {
    errorMessage.value = error?.response?.data?.errors?.roles?.[0]
      ?? error?.response?.data?.message
      ?? 'Failed to update roles.'
  } finally {
    isSaving.value = false
  }
}

onMounted(loadAvailableRoles)
</script>

<template>
  <div class="user-role-assignment">
    <h3>Roles for {{ user.name }}</h3>

    <p v-if="errorMessage" class="alert alert--error">{{ errorMessage }}</p>

    <div class="user-role-assignment__list">
      <label v-for="role in availableRoles" :key="role" class="user-role-assignment__item">
        <input
          type="checkbox"
          :checked="selectedRoles.has(role)"
          @change="toggleRole(role)"
        />
        {{ role }}
      </label>
    </div>

    <button
      v-can="'roles.assign'"
      class="btn btn--primary"
      :disabled="isSaving"
      @click="handleSave"
    >
      {{ isSaving ? 'Saving...' : 'Save Roles' }}
    </button>
  </div>
</template>