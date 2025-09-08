// vite.config.js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({

      input: {
        app: 'resources/js/app.js',
        'filament/powercheck/theme': 'resources/css/filament/powercheck/theme.css',
      },
      refresh: true,
      hotFile: 'public/hot',
    }),
  ],
})
