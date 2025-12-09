import { defineConfig } from 'vite'
import { resolve } from 'path'
import { fileURLToPath } from 'node:url'
import { dirname } from 'path'
import symfonyPlugin from "vite-plugin-symfony";
import VueRouter from 'unplugin-vue-router/vite'
import vue from '@vitejs/plugin-vue';
import { quasar, transformAssetUrls } from '@quasar/vite-plugin'
import VueI18nPlugin from '@intlify/unplugin-vue-i18n/vite'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

// https://vitejs.dev/config/
export default defineConfig(() => {
  return {
    plugins: [
      VueRouter({
        routesFolder: './assets/js/pages',
        extensions: ['.vue', '.md'],
      }),
      symfonyPlugin({
            viteDevServerHostname: 'localhost'
        }
      ),
      vue({
        template: {
          transformAssetUrls,
          compilerOptions: {
            isCustomElement: (tag) => ['uca-menu'].includes(tag),
          }}
      }),
      VueI18nPlugin({
        include: resolve(__dirname, 'assets/locales/**'),
        compositionOnly: false
      }),
      quasar({
          sassVariables: fileURLToPath(
              new URL('./assets/css/quasar-variables.sass', import.meta.url)
          )
      })
    ],
    server: {
        watch: {
            usePolling: true
        },
        // Required to listen on all interfaces
        host: '0.0.0.0',
        cors: true,
    },
    root: './assets',
    base: '/build/',
    build: {
      transpile: true,
      transpileDependencies: [
        /quasar-ui-qcalendar[\\/]src/
      ],
      manifest: true,
      assetsDir: '',
      outDir: '../public/build/',
      emptyOutDir: true,
      rollupOptions: {
          input: {
              app: resolve(__dirname, './assets/js/app.js')
          },
          output: {
              manualChunks: undefined
          },
      }
    }
  }
})
