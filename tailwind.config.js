import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import daisyui from 'daisyui';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, daisyui],

    daisyui: {
        themes: [
            {
                light: {
                    primary: "#2563eb",
                    secondary: "#4f46e5",
                    accent: "#3b82f6",
                    neutral: "#1f2937",
                    "base-100": "#ffffff",
                    info: "#3abff8",
                    success: "#36d399",
                    warning: "#fbbd23",
                    error: "#f87272",
                },
            },
            "light",
            "dark",
            "cupcake",
            "corporate",
        ],
    },
};
