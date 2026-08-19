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

  const allowedHosts = (
    env.DEV_ALLOWED_HOSTS ?? ''
  )
    .split(',')
    .map((host) => host.trim())
    .filter(Boolean)

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

      ...(allowedHosts.length > 0
        ? {
            allowedHosts,
          }
        : {}),

      proxy: {
        '/api': {
          target: proxyTarget,
          changeOrigin: true,
          secure: false,
        },
      },
    },
  }
})