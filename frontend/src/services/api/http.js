import axios from 'axios'

/**
 * Central axios instance. Sanctum SPA auth relies on cookies, so
 * withCredentials must be true and CSRF cookie must be fetched once
 * before the first stateful request (see auth.store.js -> initCsrf()).
 */
const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '/api/v1',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

// Central 401/403 handling. Individual requests can still catch locally
// for form-specific error messages — this only handles the "kick user
// back to login" / "show forbidden toast" global behaviour.
http.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status

    if (status === 401) {
      window.dispatchEvent(new CustomEvent('auth:unauthenticated'))
    }

    if (status === 403) {
      window.dispatchEvent(
        new CustomEvent('auth:forbidden', {
          detail: { message: error?.response?.data?.message },
        })
      )
    }

    return Promise.reject(error)
  }
)

export default http