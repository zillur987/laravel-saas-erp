import http from './http'

export const roleService = {
  list(search = '') {
    return http.get('/roles', { params: { search } }).then((res) => res.data.data)
  },

  show(id) {
    return http.get(`/roles/${id}`).then((res) => res.data.data)
  },

  create(payload) {
    // payload: { name, permissions: string[] }
    return http.post('/roles', payload).then((res) => res.data)
  },

  update(id, payload) {
    return http.put(`/roles/${id}`, payload).then((res) => res.data)
  },

  remove(id) {
    return http.delete(`/roles/${id}`).then((res) => res.data)
  },

  permissionMatrix() {
    // Returns permissions grouped by module: { inventory: [...], sales: [...] }
    return http.get('/roles/permission-matrix').then((res) => res.data.data)
  },
}