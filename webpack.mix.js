let mix = require('laravel-mix');
const webpack = require('webpack');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/assets/js/app.js', 'public/compiled_js')
   .react()
   .sass('resources/assets/sass/app.scss', 'public/css')
   .sass('resources/assets/sass/invoice_pdf.scss', 'public/css')
   .sass('resources/assets/sass/manifest_pdf.scss', 'public/css');

if(mix.inProduction()) {
   mix.version();
}
