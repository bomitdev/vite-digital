import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    host: '192.168.7.12',
    port: 5173, // หรือพอร์ตที่คุณต้องการ
  }, 
})
