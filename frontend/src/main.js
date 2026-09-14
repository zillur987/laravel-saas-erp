import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { canDirective } from '@/directives/can.directive'
import { useAuthStore } from '@/stores/auth.store'

const app = createApp(App)

app.use(createPinia())
app.directive('can', canDirective)
app.use(router)

window.addEventListener('auth:unauthenticated', () => {
  const authStore = useAuthStore()
  authStore.$reset()
  router.push({ name: 'login' })
})

window.addEventListener('auth:forbidden', (event) => {
  console.warn(event.detail?.message ?? 'Forbidden')
})

app.mount('#app')