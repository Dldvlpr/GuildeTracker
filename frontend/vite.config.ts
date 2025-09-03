import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'
import fs from 'node:fs'
import path from 'node:path'

// ESM-safe __dirname
const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig({
  plugins: [vue(), vueDevTools(), tailwindcss()],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    https: {
      cert: fs.readFileSync(path.resolve(__dirname, 'certs/localhost+2.pem')),
      key:  fs.readFileSync(path.resolve(__dirname, 'certs/localhost+2-key.pem')),
    },
    host: 'localhost',
    port: 5173,
    proxy: {
      '/api': {
        target: 'https://127.0.0.1:8000',
        changeOrigin: true,
        secure: false,
      },
      '/connect': {
        target: 'https://127.0.0.1:8000',
        changeOrigin: true,
        secure: false,
      }
    }
  }
})
