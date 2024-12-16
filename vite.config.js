import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vuetify from 'vite-plugin-vuetify'
export default defineConfig({
    plugins: [
        laravel([
            'resources/css/app.css',
            'resources/js/app.js',
        ]),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        vuetify(),
    ],
    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm-bundler.js',
            '@': '/resources/js',
            'assets': '/resources/assets',
            'styles': '/resources/sass',
            'utils': '/resources/js/utils'
        },
    },
    server: {
        hmr: {
            host: 'xs954629.xsrv.jp',
            port: 3000
        },
        https: true
    },
    build: {
        target: 'esnext',
        chunkSizeWarningLimit: 600 
    },
      
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler'
            }
        }
    }
});