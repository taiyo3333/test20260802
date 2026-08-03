import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    // Vite の初期値は 5173 だが、リポジトリルートの docker-compose.yml（Laravel 側）が
    // 127.0.0.1:5173 を先に押さえているため、こちらは 5174 に固定している。
    // （strictPort: true にしておくと、空いていない時に黙って別ポートへ
    //   ずれるのではなく、はっきりエラーで止まってくれる）
    port: 5174,
    strictPort: true,
  },
})
