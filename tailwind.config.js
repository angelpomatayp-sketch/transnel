import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#f3f6f8',
                    100: '#e8eef2',
                    300: '#7C8992',
                    500: '#495966',
                    700: '#263845',
                    800: '#1f303b',
                },
                accent: {
                    100: '#f4e8ad',
                    400: '#D5B23C',
                    700: '#8a6418',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
