import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
            detectTls: false,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vite Server Configuration for Multi-Tenant Development
    |--------------------------------------------------------------------------
    |
    | Configure the Vite development server to work correctly with dcms.lvh.me
    | and subdomains for Hot Module Replacement (HMR).
    |
    */
    server: {
        // Listen on all interfaces
        host: '0.0.0.0',

        // Use port 5173 (standard Vite port) to avoid conflict with Laravel on 8080
        port: 5173,

        // Configure HMR
        hmr: {
            // Use the dcms.lvh.me hostname for HMR connections
            host: 'dcms.lvh.me',

            // Use WebSocket protocol
            protocol: 'ws',
        },

        // Allow requests from all hosts in development (including ngrok)
        allowedHosts: true,

        // Configure CORS for development
        cors: true,

        // Watch these directories for changes
        watch: {
            ignored: [
                '**/node_modules/**',
                '**/vendor/**',
                '**/tenant*/**',
            ],
        },
    },

    /*
    |--------------------------------------------------------------------------
    | Vite Build Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the production build settings.
    |
    */
    build: {
        // Output directory
        outDir: 'public/build',

        // Manifest file name
        manifest: 'manifest.json',

        // Source directory
        root: 'resources/js',

        // Minification settings
        minify: 'terser',

        // Rollup options for better chunking
        rollupOptions: {
            output: {
                // Manual chunks for better caching
                manualChunks: {
                    'vue-vendor': ['vue', 'vue-router', 'pinia'],
                    'inertia-vendor': ['@inertiajs/vue3', '@inertiajs/core'],
                },
            },
        },
    },

    /*
    |--------------------------------------------------------------------------
    | Resolve Configuration
    |--------------------------------------------------------------------------
    |
    | Configure module resolution.
    |
    */
    resolve: {
        // Alias for common paths
        alias: {
            '@': '/resources/js',
            '~': '/resources',
        },
    },

    /*
    |--------------------------------------------------------------------------
    | Optimize Dependencies
    |--------------------------------------------------------------------------
    |
    | Pre-bundle dependencies for faster development.
    |
    */
    optimizeDeps: {
        include: [
            'vue',
            'vue-router',
            'pinia',
            '@inertiajs/vue3',
            '@inertiajs/core',
            '@vuepic/vue-datepicker',
            '@fullcalendar/vue3',
            '@fullcalendar/daygrid',
            '@fullcalendar/timegrid',
            '@fullcalendar/interaction',
        ],
    },
});
