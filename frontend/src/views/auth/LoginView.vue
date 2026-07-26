<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref('')

async function handleLogin() {
  try {
    await authStore.login({ email: email.value, password: password.value })
    router.push({ name: 'roles.index' })
  } catch (e) {
    error.value = 'Invalid credentials.'
  }
}
</script>

<template>
  <form @submit.prevent="handleLogin">
    <input v-model="email" type="email" placeholder="Email" />
    <input v-model="password" type="password" placeholder="Password" />
    <button type="submit">Login</button>
    <p v-if="error">{{ error }}</p>
  </form>
</template>