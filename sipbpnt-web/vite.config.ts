import { fileURLToPath, URL } from 'node:url'

import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import {
  defineConfig,
  loadEnv,
} from 'vite'
import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig(({ mode }) => {
  const env = loadEnv(
    mode,
    process.cwd(),
    '',
  )

  const customAllowedHosts = (
    env.DEV_ALLOWED_HOSTS ?? ''
  )
    .split(',')
    .map((host) => host.trim())
    .filter(Boolean)

  /*
   * Izinkan domain ngrok.
   *
   * Ngrok dapat memberikan domain:
   *
   * *.ngrok-free.dev
   * *.ngrok-free.app
   *
   * Kita whitelist keduanya agar
   * tidak perlu mengubah config setiap
   * URL ngrok berubah.
   */
  const allowedHosts = Array.from(
    new Set([
      '.ngrok-free.dev',
      '.ngrok-free.app',
      ...customAllowedHosts,
    ]),
  )

  /*
   * Laravel tetap berjalan lokal
   * pada port 8000.
   *
   * Browser tidak mengakses alamat
   * Laravel ini secara langsung.
   */
  const proxyTarget =
    env.DEV_PROXY_TARGET ||
    'http://127.0.0.1:8000'

  return {
    plugins: [
      vue(),
      vueDevTools(),
      tailwindcss(),
    ],

    resolve: {
      alias: {
        '@': fileURLToPath(
          new URL(
            './src',
            import.meta.url,
          ),
        ),
      },
    },

    server: {
      host: '0.0.0.0',

      port: 5173,

      strictPort: true,

      allowedHosts,

      proxy: {
        /*
         * API Laravel.
         */
        '/api': {
          target: proxyTarget,
          changeOrigin: true,
          secure: false,
        },

        /*
         * Laravel Sanctum CSRF.
         */
        '/sanctum': {
          target: proxyTarget,
          changeOrigin: true,
          secure: false,
        },
      },
    },
  }
})