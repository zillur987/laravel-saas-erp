<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { roleService } from '@/services/api/role.service'

const props = defineProps({
  role: { type: Object, default: null }, // null => create mode
})

const emit = defineEmits(['close', 'saved'])

const isEditMode = computed(() => props.role !== null)

const form = reactive({
  name: props.role?.name ?? '',
  permissions: new Set(props.role?.permissions ?? []),
})

const permissionGroups = ref({}) // { inventory: ['inventory.view', ...], sales: [...] }
const isLoadingMatrix = ref(true)
const isSaving = ref(false)
const errors = ref({})

async function loadMatrix() {
  isLoadingMatrix.value = true
  try {
    permissionGroups.value = await roleService.permissionMatrix()
  } finally {
    isLoadingMatrix.value = false
  }
}

function togglePermission(permissionName) {
  if (form.permissions.has(permissionName)) {
    form.permissions.delete(permissionName)
  } else {
    form.permissions.add(permissionName)
  }
}

function toggleModule(moduleName, permissions) {
  const allSelected = permissions.every((p) => form.permissions.has(p))
  permissions.forEach((p) => {
    if (allSelected) {
      form.permissions.delete(p)
    } else {
      form.permissions.add(p)
    }
  })
}

function isModuleFullySelected(permissions) {
  return permissions.length > 0 && permissions.every((p) => form.permissions.has(p))
}

async function handleSubmit() {
  isSaving.value = true
  errors.value = {}

  const payload = {
    name: form.name,
    permissions: Array.from(form.permissions),
  }

  try {
    if (isEditMode.value) {
      await roleService.update(props.role.id, payload)
    } else {
      await roleService.create(payload)
    }
    emit('saved')
  } catch (error) {
    errors.value = error?.response?.data?.errors ?? {}
  } finally {
    isSaving.value = false
  }
}

onMounted(loadMatrix)
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('close')">
    <div class="modal">
      <header class="modal__header">
        <h2>{{ isEditMode ? `Edit Role: ${role.name}` : 'Create Role' }}</h2>
        <button class="modal__close" @click="emit('close')">&times;</button>
      </header>

      <form @submit.prevent="handleSubmit">
        <div class="form-group">
          <label for="role-name">Role name</label>
          <input
            id="role-name"
            v-model="form.name"
            type="text"
            placeholder="e.g. warehouse-supervisor"
            :disabled="isEditMode && role.name === 'super-admin'"
          />
          <p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p>
        </div>

        <div class="form-group">
          <label>Permissions</label>

          <p v-if="isLoadingMatrix">Loading permissions...</p>

          <div v-else class="permission-matrix">
            <div
              v-for="(permissions, moduleName) in permissionGroups"
              :key="moduleName"
              class="permission-matrix__module"
            >
              <div class="permission-matrix__module-header">
                <label>
                  <input
                    type="checkbox"
                    :checked="isModuleFullySelected(permissions)"
                    @change="toggleModule(moduleName, permissions)"
                  />
                  <strong>{{ moduleName }}</strong>
                </label>
              </div>

              <div class="permission-matrix__items">
                <label v-for="perm in permissions" :key="perm" class="permission-matrix__item">
                  <input
                    type="checkbox"
                    :checked="form.permissions.has(perm)"
                    @change="togglePermission(perm)"
                  />
                  {{ perm.split('.')[1] }}
                </label>
              </div>
            </div>
          </div>
        </div>

        <footer class="modal__footer">
          <button type="button" class="btn" @click="emit('close')">Cancel</button>
          <button type="submit" class="btn btn--primary" :disabled="isSaving">
            {{ isSaving ? 'Saving...' : 'Save Role' }}
          </button>
        </footer>
      </form>
    </div>
  </div>
</template>