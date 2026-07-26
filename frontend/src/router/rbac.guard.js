import { useAuthStore } from '@/stores/auth.store'

/**
 * Route meta contract:
 *   meta: { requiresAuth: true, permission: 'sales.view' }
 *   meta: { requiresAuth: true, permission: ['sales.create', 'sales.update'] } // ANY
 *   meta: { requiresAuth: true, permission: [...], requireAll: true }         // ALL
 *   meta: { requiresAuth: true, role: 'manager' }
 *
 * Register in router/index.js with: router.beforeEach(rbacGuard)
 */
export async function rbacGuard(to, from, next) {
  const authStore = useAuthStore()

  if (!authStore.isReady) {
    try {
      await authStore.fetchCurrentUser()
    } catch {
      // not authenticated — fall through, handled below
    }
  }

  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth)

  if (requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  const requiredPermission = to.meta.permission
  const requiredRole = to.meta.role

  if (requiredPermission) {
    const allowed = Array.isArray(requiredPermission)
      ? to.meta.requireAll
        ? authStore.canAll(requiredPermission)
        : authStore.canAny(requiredPermission)
      : authStore.can(requiredPermission)

    if (!allowed) {
      return next({ name: 'forbidden' })
    }
  }

  if (requiredRole && !authStore.hasRole(requiredRole)) {
    return next({ name: 'forbidden' })
  }

  return next()
}