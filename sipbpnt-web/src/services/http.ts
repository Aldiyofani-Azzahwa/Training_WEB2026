import axios from 'axios'

const apiUrl =
  import.meta.env.VITE_API_URL ||
  'http://localhost:8000'

export const http = axios.create({
  baseURL: apiUrl,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  timeout: 30_000,
})

http.interceptors.response.use(
  (response) => response,

  async (error) => {
    if (
      error.response?.status === 419 &&
      error.config &&
      !error.config.__csrfRetried
    ) {
      error.config.__csrfRetried = true

      await http.get('/sanctum/csrf-cookie')

      return http.request(error.config)
    }

    return Promise.reject(error)
  },
)