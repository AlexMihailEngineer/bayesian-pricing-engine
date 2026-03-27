import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    host: true,      // Listen on all addresses (0.0.0.0)
    port: 5173,      // Force this port
    strictPort: true, // Fail if 5173 is busy instead of switching to 5174
    proxy: {
      '/api': {
        target: 'http://web:80',
        changeOrigin: true,
      },
    },
  },
});