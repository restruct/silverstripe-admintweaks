// More or less relevant references:
// https://laravel.com/docs/5.7/mix
// https://laravel-mix.com/docs/6.0/api
// https://northcreation.agency/laravel-mix-with-silverstripe/
// https://github.com/gorriecoe/silverstripe-mix/blob/master/package.json

const mix = require('laravel-mix');
// const webpack = require('webpack');

// See: https://laravel-mix.com/docs/6.0/options
mix.options({
  // processCssUrls: false,
  // fileLoaderDirs: {
  //     fonts: 'fonts',
  // }
});

// this keeps relative image urls in js/scss intact, else they're converted to absolute
// (unless processCssUrls is set to false, but then no images will be copied to dist/images either...)
mix.setResourceRoot('../'); // eg from /css/here.css to /images/* or /fonts/*

// Generate sourcemaps in dev (not in prod)
mix.sourceMaps(null, 'source-map');

// Set the path to which all public assets should be compiled
mix.setPublicPath('client/dist');

// Examples: https://laravel-mix.com/docs/6.0/examples
//mix.sass('client/src/scss/admintweaks.scss', 'client/dist/css/admintweaks.css');
mix.sass('client/src/scss/admintweaks.scss', 'css');

// mix.scripts = basic concattenation
// mix.babel = concattenation + babel (ES2015 -> vanilla)
// mix.js = components, react, vue, etc
//   -> include { "presets": ["@babel/preset-react"] } in .babelrc for correct transpilation
//   (or add .vue()/.react() in mix.js)
// mix.js('client/src/js/main.js', 'client/dist/js/main.js').vue();
// .extract() results in main.js plus separate vendor.js () + manifest.js which all three have to get loaded
// mix.js([ 'client/src/js/one.js', 'client/src/js/two.js', ], 'js/app.js').extract();
mix.js([ 'client/src/js/admintweaks.js', ], 'js');

// Make a module available as a variable in every other module required by webpack
// Makes webpack prepend var $ = require('jquery') to every $, jQuery or window.jQuery
// This will result in jQuery being compiled-in, even though it may be provided externally (see webpack.externals)
mix.autoload({
//    jquery: ['$', 'jQuery', 'window.jQuery'],
//    underscore: ['_', 'underscore'],
});

// Webpack config overrides, mix will deep merge https://laravel-mix.com/docs/2.1/quick-webpack-configuration#using-a-callback-function
mix.webpackConfig({
    externals: {
        // Prevent bundling of certain imported packages and instead retrieve these external dependencies at runtime
        // (the created bundle relies on that dependency to be present in the end-user application environment)
        // Externals will not be compiled-in (eg import $ from 'jQuery', combined with external 'jquery': 'jQuery' means jQuery gets provided externally)
        // Mainly for SS admin modules, for provided externals see: https://github.com/silverstripe/webpack-config/blob/master/js/externals.js
        'jquery': 'jQuery',
        'react': 'React',
        'lib/Injector': 'Injector',
    }

//     // Devtool option controls if and how source maps are generated -> equivalent of mix.sourceMaps(?)
//     devtool: 'source-map',
//
//     plugins: [
//       // ProvidePlugin: automatically load modules instead of having to import or require them everywhere
//       // ProvidePlugin is equivalent to mix.autoload() so the below seems unnecessary
//       // https://webpack.js.org/plugins/provide-plugin/#usage-jquery
//       new webpack.ProvidePlugin({
//         $: 'jquery',
//         jQuery: 'jquery',
//         'window.jQuery': 'jquery',
//         // _: "underscore",
//         // underscore: 'underscore'
//       })
//     ],
});
