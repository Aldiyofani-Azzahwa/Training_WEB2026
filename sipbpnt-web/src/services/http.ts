import axios from 'axios'
export const http = axios.create({
  baseURL: '/',
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
    /*
     * Jika session / CSRF expired,
     * ambil CSRF cookie baru satu kali
     * kemudian ulang request.
     */
    if (
      error.response?.status === 419 &&
      error.config &&
      !error.config.__csrfRetried
    ) {
      error.config.__csrfRetried = true

      await http.get(
        '/sanctum/csrf-cookie',
      )

      return http.request(
        error.config,
      )
    }

    return Promise.reject(error)
  },
)