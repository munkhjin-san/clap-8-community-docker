/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./resources/**/*/*.vue",
    ],
    theme: {
        extend: {
            screens: {
                'sm959' : '960px',
                'under960': {'max': '959px'},
            }
        },
    },
    corePlugins: {
        preflight: false, 
    },
    plugins: [],
}

