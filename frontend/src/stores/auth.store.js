import { defineStore } from 'pinia'
import http from '@/services/api/http'
import { userAccessService } from '@/services/api/userAccess.service'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    roles: [],
    permissions: [],
    isReady: false, // true once we've attempted to hydrate from /me at least once
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,

    /**
     * @returns {(permission: string) => boolean}
     */
    can: (state) => (permission) => state.permissions.includes(permission),

    /**
     * True if the user has ANY of the given permissions.
     * @returns {(permissions: string[]) => boolean}
     */
    canAny: (state) => (permissionList) =>
      permissionList.some((p) => state.permissions.includes(p)),

    /**
     * True only if the user has EVERY given permission.
     * @returns {(permissions: string[]) => boolean}
     */
    canAll: (state) => (permissionList) =>
      permissionList.every((p) => state.permissions.includes(p)),

    hasRole: (state) => (role) => state.roles.includes(role),
  },

  actions: {
    /**
     * Must be called once before the first stateful (cookie-auth) request —
     * Sanctum SPA auth needs the CSRF cookie set first.
     */
    async initCsrf() {
      await http.get('/sanctum/csrf-cookie', { baseURL: import.meta.env.VITE_APP_URL })
    },

    async login(credentials) {
      await this.initCsrf()
      await http.post('/login', credentials, { baseURL: import.meta.env.VITE_APP_URL })
      await this.fetchCurrentUser()
    },

    async logout() {
      await http.post('/logout', {}, { baseURL: import.meta.env.VITE_APP_URL })
      this.$reset()
    },

    /**
     * Hydrates user + roles + permissions from the backend.
     * Call this on app boot and after login.
     */
    async fetchCurrentUser() {
      try {
        const user = await userAccessService.me()
        this.user = user
        this.roles = user.roles ?? []
        this.permissions = user.permissions ?? []
      } catch (error) {
        this.user = null
        this.roles = []
        this.permissions = []
        throw error
      } finally {
        this.isReady = true
      }
    },
  },
})