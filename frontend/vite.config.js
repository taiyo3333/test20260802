import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig(({ command }) => ({
  plugins: [react()],

  // 本番は https://ドメイン/app/ の下に置くので、JS や CSS の参照先も
  // /app/ 始まりにしてもらう。これが無いと /assets/... を探して 404 になる。
  // 開発サーバー（command === 'serve'）では今まで通りルートで動かす。
  base: command === 'build' ? '/app/' : '/',

  build: {
    // ビルド結果を Laravel の公開ディレクトリの中に出す。
    // こうすると nginx の root（/data/public）配下になるので、
    // 追加のドメインもポートも要らずに同じサーバーから配れる。
    outDir: '../src/public/app',
    // outDir がプロジェクト外なので、消してよいことを明示する必要がある
    emptyOutDir: true,
  },

  server: {
    // Vite の初期値は 5173 だが、リポジトリルートの docker-compose.yml（Laravel 側）が
    // 127.0.0.1:5173 を先に押さえているため、こちらは 5174 に固定している。
    // （strictPort: true にしておくと、空いていない時に黙って別ポートへ
    //   ずれるのではなく、はっきりエラーで止まってくれる）
    port: 5174,
    strictPort: true,
  },
}))
