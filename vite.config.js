/* eslint-env node */
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    base: './',
    plugins: [vue()],
    server: {
      host: env.VITE_SERVER_HOST || '0.0.0.0',
      port: parseInt(env.VITE_SERVER_PORT) || 5173,
      proxy: {
        // backend
        '/backend': {
          target: env.VITE_BACKEND_URL,
          changeOrigin: true,
          rewrite: (path) =>
            path.replace(/^\/backend/, '/vue-app/vite-digital/backend'),
          configure: (proxy) => {
            proxy.on('error', (err) => {
              console.log('proxy error', err);
            });
          }
        },
        // api-digital
        '/api-digital': {
          target: env.VITE_API_DIGITAL_TARGET,
          changeOrigin: true,
          rewrite: (path) =>
            path.replace(/^\/api-digital/, '/vue-app/vite-digital/backend/api-digital'),
          configure: (proxy) => {
            proxy.on('error', (err) => {
              console.log('proxy error', err);
            });
          }
        },
        // api-hosxe
        '/api-hosxe': {
          target: env.VITE_API_HOSXE_TARGET,
          changeOrigin: true,
          rewrite: (path) =>
            path.replace(/^\/api-hosxe/, '/vue-app/vite-digital/backend/api-hosxe'),
          configure: (proxy) => {
            proxy.on('error', (err) => {
              console.log('proxy error', err);
            });
          }
        },
        // api-hosoffice
        '/api-hosoffice': {
          target: env.VITE_API_HOSXE_TARGET,
          changeOrigin: true,
          rewrite: (path) =>
            path.replace(/^\/api-hosoffice/, '/vue-app/vite-digital/backend/api-hosoffice'),
          configure: (proxy) => {
            proxy.on('error', (err) => {
              console.log('proxy error', err);
            });
          }
        }
      }
    },
  }
})