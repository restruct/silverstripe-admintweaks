const mix = require('laravel-mix');
const webpack = require('webpack');
mix.options({
    processCssUrls: false,
});
mix.setPublicPath('client/dist');

mix.sass('client/src/scss/admintweaks.scss', 'client/dist/css/admintweaks.css')
//    .sass('client/src/sass/editor.scss', 'client/dist/css/')
    ;

//mix.js([
////    'client/plugins/bootstrap-multiselect/dist/js/bootstrap-multiselect.js',
//    'client/src/js/webpacktest.js',
//], 'client/dist/js/webpacktest.js').extract();


mix.autoload({
    jquery: ['$', 'window.jQuery'],
    underscore: ['_', 'underscore']
});

mix.webpackConfig(webpack => {
    return {
        plugins: [
            new webpack.ProvidePlugin({
                $: 'jquery',
                jQuery: 'jquery',
                'window.jQuery': 'jquery',
                _: "underscore",
                underscore: 'underscore'
            })
        ],
        externals: {
        //    'components/FormBuilder/FormBuilder': 'FormBuilder',
            jQuery: 'jQuery',
        }
    }
});