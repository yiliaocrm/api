import vue from "@vitejs/plugin-vue"
import vueJsx from "@vitejs/plugin-vue-jsx"
import tailwindcss from "tailwindcss"
import autoprefixer from "autoprefixer"
import { defineConfig, loadEnv } from "vite"
import { lazyImport, VxeResolver } from 'vite-plugin-lazy-import'

export default defineConfig(({ mode }) => {
	const env = loadEnv(mode, process.cwd(), '')
	const base = mode === "development" ? "/" : "/dist/admin/"
	const isDevelopment = mode === "development"

	return {
		base: base,
		plugins: [
			vue(),
			vueJsx(),
			lazyImport({
				resolvers: [
					VxeResolver({
						libraryName: 'vxe-table'
					}),
					VxeResolver({
						libraryName: 'vxe-pc-ui'
					})
				]
			})
		],
		resolve: {
			alias: {
				"@": "/src",
			},
			extensions: [".mjs", ".js", ".ts", ".jsx", ".tsx", ".json", ".vue"],
		},
		server: {
			port: 2800,
			proxy: {
				"/api": {
					target: env.VITE_API_PROXY_TARGET,
					changeOrigin: true,
					rewrite: (path) => path.replace(/^\/api/, ""),
				}
			}
		},
		build: {
			outDir: "../../public/dist/admin",
			emptyOutDir: true,
			sourcemap: isDevelopment ? "inline" : false,
		},
		css: {
			postcss: {
				plugins: [tailwindcss, autoprefixer],
			},
			preprocessorOptions: {
				scss: {
					silenceDeprecations: ['legacy-js-api']
				}
			}
		},
		test: {
			globals: true,
			environment: "happy-dom",
			include: ["tests/**/*.{test,spec}.{js,jsx}"],
			coverage: {
				provider: "v8",
				reporter: ["text", "json", "html"],
				reportsDirectory: "./coverage",
			},
		},
	}
})
