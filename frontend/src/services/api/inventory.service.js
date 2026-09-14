import http from './http'

export const inventoryService = {
  list(params = {}) {
    return http.get('/inventory/products', { params }).then((res) => res.data)
  },

  show(id) {
    return http.get(`/inventory/products/${id}`).then((res) => res.data.data)
  },

  lookup(q) {
    return http.get('/inventory/products/lookup', { params: { q } }).then((res) => res.data.data)
  },

  lowStock() {
    return http.get('/inventory/products/low-stock').then((res) => res.data.data)
  },

  create(payload) {
    return http.post('/inventory/products', payload).then((res) => res.data)
  },

  update(id, payload) {
    return http.put(`/inventory/products/${id}`, payload).then((res) => res.data)
  },

  remove(id) {
    return http.delete(`/inventory/products/${id}`).then((res) => res.data)
  },

  adjust(id, payload) {
    return http.post(`/inventory/products/${id}/adjust`, payload).then((res) => res.data)
  },

  adjustments(params = {}) {
    return http.get('/inventory/adjustments', { params }).then((res) => res.data)
  },

  categories() {
    return http.get('/inventory/categories').then((res) => res.data.data)
  },

  createCategory(payload) {
    return http.post('/inventory/categories', payload).then((res) => res.data)
  },

  updateCategory(id, payload) {
    return http.put(`/inventory/categories/${id}`, payload).then((res) => res.data)
  },

  removeCategory(id) {
    return http.delete(`/inventory/categories/${id}`).then((res) => res.data)
  },

  exportCsv() {
    return http.get('/inventory/export', { responseType: 'blob' }).then((res) => res.data)
  },
}
