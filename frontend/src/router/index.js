import { createRouter, createWebHistory } from 'vue-router'
import { rbacGuard } from './rbac.guard'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
  },
  {
    path: '/403',
    name: 'forbidden',
    component: () => import('@/views/errors/ForbiddenView.vue'),
  },
  {
    path: '/',
    component: () => import('@/components/AppShell.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('@/views/HomeView.vue'),
      },
      {
        path: 'inventory',
        name: 'inventory.index',
        component: () => import('@/views/inventory/InventoryListView.vue'),
        meta: { permission: 'inventory.view' },
      },
      {
        path: 'pos',
        name: 'pos.terminal',
        component: () => import('@/views/pos/PosTerminalView.vue'),
        meta: { permission: 'pos.view' },
      },
      {
        path: 'pos/sales',
        name: 'pos.sales',
        component: () => import('@/views/pos/PosSalesView.vue'),
        meta: { permission: 'pos.view' },
      },
      {
        path: 'roles',
        name: 'roles.index',
        component: () => import('@/views/rbac/RoleManagementView.vue'),
        meta: { permission: 'roles.view' },
      },
      {
        path: 'sales/orders/:id/approve',
        name: 'sales.orders.approve',
        component: () => import('@/views/sales/ApproveOrderView.vue'),
        meta: { permission: 'sales.approve' },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(rbacGuard)

export default router
