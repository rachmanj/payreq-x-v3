/** @type {import('tailwindcss').Config} */
module.exports = {
    prefix: 'tw-',
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    corePlugins: {
        preflight: false,
    },
    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    500: '#667eea',
                    600: '#5a67d8',
                    700: '#764ba2',
                },
            },
            boxShadow: {
                card: '0 0 20px rgba(0, 0, 0, 0.08)',
                'card-hover': '0 5px 30px rgba(0, 0, 0, 0.12)',
            },
        },
    },
    plugins: [],
};
