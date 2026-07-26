import { useAuthStore } from '@/stores/auth.store'

/**
 * v-can directive.
 *
 * Usage:
 *   <button v-can="'sales.create'">New Order</button>
 *   <button v-can="['sales.create', 'sales.update']">Save</button>            // ANY
 *   <button v-can.all="['sales.create', 'sales.update']">Save</button>        // ALL
 *
 * This is UI-level only — it hides the element. It is NOT a security
 * boundary; the backend route middleware / policy is the real gate.
 * Hides via display:none rather than v-if/unmounting, so the directive
 * stays framework-agnostic and doesn't fight Vue's own conditional
 * rendering when combined with v-if/v-show on the same element.
 */
export const canDirective = {
  mounted(el, binding) {
    applyPermissionCheck(el, binding)
  },
  updated(el, binding) {
    applyPermissionCheck(el, binding)
  },
}

function applyPermissionCheck(el, binding) {
  const authStore = useAuthStore()
  const value = binding.value
  const requireAll = binding.modifiers.all === true

  let allowed

  if (Array.isArray(value)) {
    allowed = requireAll ? authStore.canAll(value) : authStore.canAny(value)
  } else {
    allowed = authStore.can(value)
  }

  if (!allowed) {
    // Comment node placeholder keeps Vue's patching happy across re-renders.
    if (el.parentNode && el.__vCanPlaceholder !== true) {
      el.style.display = 'none'
      el.setAttribute('data-v-can-hidden', 'true')
    }
  } else if (el.hasAttribute('data-v-can-hidden')) {
    el.style.display = ''
    el.removeAttribute('data-v-can-hidden')
  }
}