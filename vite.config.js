import glob from 'glob';
import path from 'path';
import vue from '@vitejs/plugin-vue';

const moduleInputs = glob.sync('classes/Modules/*/www/js/entry.{js,jsx}')
    .map(file => ['modules/'+file.split('/')[2], file]);

/** @type {import('vite').UserConfig} */
export default {
    build: {
        rollupOptions: {
            input: {
                main: 'www/themes/new/js/main.js',
                ...Object.fromEntries(moduleInputs)
            }
        },
        manifest: true,
        outDir: 'www/dist',
    },
    plugins: [vue()],
    mode: 'development',
    resolve: {
        alias: {
            '@theme': path.resolve(__dirname, 'www/themes/new/js')
        }
    }
}