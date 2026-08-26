import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/legajos/theme.css',
            ],
            refresh: true,
        }),

        // CORREGIDO: antes decía tailwindcss({ include: ['resources/css/app.css'] })
        // con el comentario "para que NO toque la carpeta de Filament".
        //
        // @tailwindcss/vite no acepta opciones: el objeto se descartaba en
        // silencio y Tailwind procesaba theme.css igual. La premisa era falsa.
        //
        // Que lo procese es lo correcto, además: theme.css importa el tema de
        // Filament, que a su vez hace @import 'tailwindcss'. Excluirlo de
        // verdad rompería los estilos base del panel.
        tailwindcss(),
    ],

    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});