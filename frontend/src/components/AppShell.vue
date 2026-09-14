<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { usePermissions } from '@/composables/usePermissions'

const authStore = useAuthStore()
const { can } = usePermissions()
const router = useRouter()

async function logout() {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="erp-shell">
    <nav class="erp-nav">
      <RouterLink class="erp-nav__brand" to="/">ERP</RouterLink>
      <RouterLink v-if="can('inventory.view')" to="/inventory">Inventory</RouterLink>
      <RouterLink v-if="can('pos.view')" to="/pos">POS</RouterLink>
      <RouterLink v-if="can('pos.view')" to="/pos/sales">Receipts</RouterLink>
      <RouterLink v-if="can('roles.view')" to="/roles">Roles</RouterLink>
      <span class="erp-nav__spacer" />
      <span v-if="authStore.user">{{ authStore.user.name }}</span>
      <button class="btn btn--sm" type="button" @click="logout">Logout</button>
    </nav>
    <main class="erp-main">
      <RouterView />
    </main>
  </div>
</template>
