import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// BizyDoc is a separate bundle that runs inside BizyCore's page: Vue is not bundled again, it is BizyCore's window.Vue.
export default defineConfig( {
	root: 'ui',
	plugins: [ vue() ],
	define: { 'process.env.NODE_ENV': '"production"' },
	build: {
		outDir: '../build',
		emptyOutDir: true,
		cssCodeSplit: false,
		lib: { entry: 'main.js', name: 'BizyDoc', formats: [ 'iife' ], fileName: () => 'bizydoc.js', cssFileName: 'bizydoc' },
		rollupOptions: { external: [ 'vue' ], output: { globals: { vue: 'Vue' } } },
	},
} );
