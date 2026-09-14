import { defineStore } from 'pinia'
import http, { getToken, setToken } from '@/services/api/http'
import { userAccessService } from '@/services/api/userAccess.service'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    roles: [],
    permissions: [],
    isReady: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,

    can: (state) => (permission) => state.permissions.includes(permission),

    canAny: (state) => (permissionList) =>
      permissionList.some((p) => state.permissions.includes(p)),

    canAll: (state) => (permissionList) =>
      permissionList.every((p) => state.permissions.includes(p)),

    hasRole: (state) => (role) => state.roles.includes(role),
  },

  actions: {
    hydrateFromUser(user) {
      this.user = user
      this.roles = user?.roles ?? []
      this.permissions = user?.permissions ?? []
    },

    async login(credentials) {
      const { data } = await http.post('/login', credentials)
      setToken(data.access_token)
      this.hydrateFromUser(data.data)
      this.isReady = true
    },

    async logout() {
      try {
        await http.post('/logout')
      } catch {
        // token may already be invalid
      }
      setToken(null)
      this.$reset()
      this.isReady = true
    },

    async fetchCurrentUser() {
      if (!getToken()) {
        this.user = null
        this.roles = []
        this.permissions = []
        this.isReady = true
        return
      }
      try {
        const user = await userAccessService.me()
        this.hydrateFromUser(user)
      } catch (error) {
        setToken(null)
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
