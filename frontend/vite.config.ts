import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'
import fs from 'node:fs'
import path from 'node:path'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig({
  plugins: [vue(), vueDevTools(), tailwindcss()],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {


    https: (() => {
      const certDir = path.resolve(__dirname, 'certs')
      const localhostCert = path.join(certDir, 'localhost.pem')
      const localhostKey = path.join(certDir, 'localhost-key.pem')
      const legacyCert = path.join(certDir, 'cert.pem')
      const legacyKey = path.join(certDir, 'key.pem')

      const useLocalhost = fs.existsSync(localhostCert) && fs.existsSync(localhostKey)
      const useLegacy = fs.existsSync(legacyCert) && fs.existsSync(legacyKey)

      if (!useLocalhost && !useLegacy) {

        return undefined
      }

      const certPath = useLocalhost ? localhostCert : legacyCert
      const keyPath = useLocalhost ? localhostKey : legacyKey

      return {
        cert: fs.readFileSync(certPath),
        key: fs.readFileSync(keyPath),
      }
    })(),
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
