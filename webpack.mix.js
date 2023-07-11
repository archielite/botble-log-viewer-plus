const mix = require('laravel-mix')
const path = require('path')
const tailwindcss = require('tailwindcss')
const directory = path.basename(path.resolve(__dirname))

const source = `platform/plugins/${directory}`
const dist = `public/vendor/core/plugins/log-viewer-plus`

mix.sass(`${source}/resources/sass/app.scss`, `${dist}/css`, {}, [tailwindcss(`${source}/tailwind.config.js`)])
    .vue()
    .js(`${source}/resources/js/app.js`, `${dist}/js`)

if (mix.inProduction()) {
    mix.copy(`${source}/public/images`, `${dist}/images`)
        .copy(`${dist}/css/app.css`, `${source}/public/css`)
        .copy(`${dist}/js/app.js`, `${source}/public/js`)
}
