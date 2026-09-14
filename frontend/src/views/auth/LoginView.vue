<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useRouter, useRoute } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const email = ref('admin@example.com')
const password = ref('password')
const error = ref('')

function homeRoute() {
  if (route.query.redirect) return String(route.query.redirect)
  if (authStore.can('pos.view')) return { name: 'pos.terminal' }
  if (authStore.can('inventory.view')) return { name: 'inventory.index' }
  return { name: 'home' }
}

async function handleLogin() {
  error.value = ''
  try {
    await authStore.login({ email: email.value, password: password.value })
    await router.push(homeRoute())
  } catch (e) {
    error.value = e?.response?.data?.message ?? 'Invalid credentials.'
  }
}
</script>

<template>
  <form class="erp-form" style="max-width: 360px; margin: 80px auto; text-align: left" @submit.prevent="handleLogin">
    <h1>Sign in</h1>
    <label>Email<input v-model="email" type="email" required /></label>
    <label>Password<input v-model="password" type="password" required /></label>
    <button class="btn btn--primary" type="submit">Login</button>
    <p v-if="error" class="alert alert--error">{{ error }}</p>
  </form>
</template>
