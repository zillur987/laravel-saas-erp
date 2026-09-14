<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

const router = useRouter()
const authStore = useAuthStore()

onMounted(() => {
  if (authStore.can('pos.view')) {
    router.replace({ name: 'pos.terminal' })
  } else if (authStore.can('inventory.view')) {
    router.replace({ name: 'inventory.index' })
  } else if (authStore.can('roles.view')) {
    router.replace({ name: 'roles.index' })
  } else {
    router.replace({ name: 'forbidden' })
  }
})
</script>

<template>
  <p>Loading...</p>
</template>
