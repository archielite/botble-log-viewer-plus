const path = require('path')
const directory = path.basename(path.resolve(__dirname))
const source = 'platform/plugins/' + directory
const colors = require('tailwindcss/colors')

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [`${source}/resources/**/*.blade.php`, `${source}/resources/**/*.js`, `${source}/resources/**/*.vue`],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                brand: colors.sky,
                gray: colors.zinc,
            },
            width: {
                88: '22rem',
            },
            padding: {
                88: '22rem',
            },
            transitionProperty: {
                width: 'width',
            },
        },
    },
    plugins: [],
}
