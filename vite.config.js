import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// WPFlatSign is a separate bundle that runs inside WPFlatCore's page: Vue is not bundled again, it is WPFlatCore's window.Vue.
export default defineConfig( {
	root: 'ui',
	plugins: [ vue() ],
	define: { 'process.env.NODE_ENV': '"production"' },
	build: {
		outDir: '../build',
		emptyOutDir: true,
		cssCodeSplit: false,
		lib: { entry: 'main.js', name: 'WPFlatSign', formats: [ 'iife' ], fileName: () => 'wpflatsign.js', cssFileName: 'wpflatsign' },
		rollupOptions: { external: [ 'vue' ], output: { globals: { vue: 'Vue' } } },
	},
} );
