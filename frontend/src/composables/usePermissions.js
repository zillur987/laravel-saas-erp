import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth.store'

/**
 * Composable for permission checks inside <script setup>, where the
 * v-can directive isn't expressive enough (e.g. computed classes,
 * conditional API calls, route logic).
 *
 * Example:
 *   const { can, canAny, hasRole } = usePermissions()
 *   if (can('sales.approve')) { ... }
 */
export function usePermissions() {
  const authStore = useAuthStore()

  const can = (permission) => authStore.can(permission)
  const canAny = (permissions) => authStore.canAny(permissions)
  const canAll = (permissions) => authStore.canAll(permissions)
  const hasRole = (role) => authStore.hasRole(role)

  const permissions = computed(() => authStore.permissions)
  const roles = computed(() => authStore.roles)

  return { can, canAny, canAll, hasRole, permissions, roles }
}