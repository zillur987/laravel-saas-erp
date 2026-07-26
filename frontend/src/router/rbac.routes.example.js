/**
 * Example — merge these route objects into your existing router/index.js
 * routes array. Shows how meta.permission ties into rbac.guard.js.
 */
export const rbacExampleRoutes = [
  {
    path: '/roles',
    name: 'roles.index',
    component: () => import('@/views/rbac/RoleManagementView.vue'),
    meta: { requiresAuth: true, permission: 'roles.view' },
  },
  {
    path: '/inventory',
    name: 'inventory.index',
    component: () => import('@/views/inventory/InventoryListView.vue'),
    meta: { requiresAuth: true, permission: 'inventory.view' },
  },
  {
    path: '/sales/orders/:id/approve',
    name: 'sales.orders.approve',
    component: () => import('@/views/sales/ApproveOrderView.vue'),
    meta: { requiresAuth: true, permission: 'sales.approve' },
  },
  {
    path: '/403',
    name: 'forbidden',
    component: () => import('@/views/errors/ForbiddenView.vue'),
    meta: { requiresAuth: false },
  },
]