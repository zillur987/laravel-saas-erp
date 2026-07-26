import { createRouter, createWebHistory } from 'vue-router'
import { rbacExampleRoutes } from './rbac.routes.example' // rename/inline once confirmed working

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
  },
  ...rbacExampleRoutes,
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router