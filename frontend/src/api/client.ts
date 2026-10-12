import axios from 'axios'

const client = axios.create({
  // API is served under the same base path as the app (e.g. /demo/api) unless VITE_API_URL overrides it
  baseURL: import.meta.env.VITE_API_URL ?? import.meta.env.BASE_URL.replace(/\/$/, ''),
  headers: { 'Content-Type': 'application/json' },
})

client.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

client.interceptors.response.use(
  (res) => res,
  (err) => {
    // Only an expired/invalid token means "back to login". A 401 on an
    // unauthenticated request (e.g. wrong password on /api/login) is for the caller to show.
    if (err.response?.status === 401 && err.config?.headers?.Authorization) {
      localStorage.removeItem('token')
      localStorage.removeItem('crm-auth')
      window.location.href = `${import.meta.env.BASE_URL}login`
    }
    return Promise.reject(err)
  },
)

export default client
