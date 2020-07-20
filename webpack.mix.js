let mix = require('laravel-mix');

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

mix.react('resources/assets/js/app.js', 'public/compiled_js')
   .react('resources/assets/js/components/partials/App.js', 'public/compiled_js')
   .react('resources/assets/js/components/admin/AppSettings.js', 'public/compiled_js')
   .react('resources/assets/js/components/bills/Bill.js', 'public/compiled_js')
   .react('resources/assets/js/components/dispatch/Dispatch.js', 'public/compiled_js')
   .react('resources/assets/js/components/invoices/Invoices.js', 'public/compiled_js')
   .react('resources/assets/js/components/ratesheets/Ratesheet.js', 'public/compiled_js')
   .react('resources/assets/js/components/ratesheets/Ratesheets.js', 'public/compiled_js')
   .sass('resources/assets/sass/app.scss', 'public/css');
