/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./resources/**/*/*.vue",
    ],
    safelist: [
        {
            pattern: /col-span-(1|2|3|4)/,
        },
    ],
    theme: {
        extend: {
            screens: {
                'sm959' : '960px',
                'under960': {'max': '959px'},
                'under640': {'max': '639px'},
                'under500': {'max': '499px'},
                'under400': {'max': '399px'},
                'under350': {'max': '349px'},
            }
        },
    },
    corePlugins: {
        preflight: false, 
    },
    plugins: [],
}

