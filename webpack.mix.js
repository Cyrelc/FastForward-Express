let mix = require('laravel-mix');
const TerserPlugin = require('terser-webpack-plugin');

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
   .sass('resources/assets/sass/manifest_pdf.scss', 'public/css')
   .sourceMaps();

mix.scripts([
   'node_modules/bootstrap/dist/js/bootstrap.min.js',
   'resources/assets/js/public/toastr.min.js',
   'resources/assets/js/public/utils.js'
], 'public/compiled_js/public.js');

mix.styles([
   'resources/assets/sass/public/toastr.min.css',
   'node_modules/bootstrap/dist/css/bootstrap.min.css',
   'resources/assets/sass/public/login.css',
   'resources/assets/sass/public/app2.css',
], 'public/css/public.css');

if(mix.inProduction()) {
   mix.version();
   mix.webpackConfig({
      optimization: {
         minimize: true,
         minimizer: [new TerserPlugin({
            terserOptions: {
               compress: {
                  drop_console: true,
               }
            }
         })]
      }
   })
}
