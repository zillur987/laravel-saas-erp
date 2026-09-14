import http from './http'

export const posService = {
  catalog(params = {}) {
    return http.get('/pos/catalog', { params }).then((res) => res.data.data)
  },

  checkout(payload) {
    return http.post('/pos/checkout', payload).then((res) => res.data)
  },

  sales(params = {}) {
    return http.get('/pos/sales', { params }).then((res) => res.data)
  },

  show(id) {
    return http.get(`/pos/sales/${id}`).then((res) => res.data.data)
  },

  voidSale(id) {
    return http.post(`/pos/sales/${id}/void`).then((res) => res.data)
  },

  summary(params = {}) {
    return http.get('/pos/summary', { params }).then((res) => res.data.data)
  },
}

export const customerService = {
  list(search = '') {
    return http.get('/crm/customers', { params: { search } }).then((res) => res.data.data)
  },
}
