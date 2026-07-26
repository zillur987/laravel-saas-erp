import http from './http'

export const userAccessService = {
  syncRoles(userId, roles) {
    return http.post(`/users/${userId}/roles`, { roles }).then((res) => res.data)
  },

  syncDirectPermissions(userId, permissions) {
    return http.post(`/users/${userId}/permissions`, { permissions }).then((res) => res.data)
  },

  me() {
    return http.get('/me').then((res) => res.data.data)
  },
}