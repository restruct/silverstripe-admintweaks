// Builds client/dist with Vite (replaces laravel-mix, 2026-11). Run it through package.json:
//   yarn build   minified, no source maps (what gets committed)
//   yarn dev     unminified, with inline source maps
//   yarn watch   as dev, rebuilding on change
//
// Why a script and not a vite.config file: a classic (non-module) script must be built as an IIFE,
// and Rolldown builds an IIFE from ONE entry only ("multiple inputs are not supported when
// output.codeSplitting is false"). So every classic script is its own build, and the styles are
// another. Vite's JS API runs them one after another from one shared config; `vite build` on the
// command line can only run one.
//
// Output names and paths are the ones laravel-mix produced, because PHP and YAML load them by plain
// path (_config/admin.yml extra_requirements_css/javascript, LeftAndMainExtension::PAGE_ICONS_CSS,
// MultivalueSortField: Requirements::css) and composer extra.expose publishes client/dist.
// So: no hashes, explicit file names, no manifest, and client/dist is never emptied.
import { build } from 'vite';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('.', import.meta.url));
const args = process.argv.slice(2);
const watch = args.includes('--watch');
const dev = watch || args.includes('--dev');

// Admin globals an import may resolve to, instead of being bundled. Same list as the webpack
// externals in the old webpack.mix.js (names as in @silverstripe/webpack-config js/externals.js).
// admintweaks.js imports none of them today (it uses the global jQuery directly), so this only
// keeps a future `import $ from 'jquery'` from bundling a second jQuery into the admin.
const globals = {
    'jquery': 'jQuery',
    'react': 'React',
    'lib/Injector': 'Injector',
};

// One entry per output file; the key is the output file name (without extension)
const parts = [
    {
        // Styles: one CSS file per .scss entry (Vite drops the empty JS chunks of CSS-only entries)
        input: {
            // Main styles, always loaded (_config/admin.yml extra_requirements_css)
            'admintweaks': 'client/src/scss/admintweaks.scss',
            // SVG page icons, a file of their own so LeftAndMain.page_icons: false can leave them out
            // (required by LeftAndMainExtension)
            'admintweaks-page-icons': 'client/src/scss/admintweaks-page-icons.scss',
            // Required by MultivalueSortField itself
            'multivaluesortfield': 'client/src/scss/multivaluesortfield.scss',
            // (Bootstrap Icons are not built here: LeftAndMainExtension loads them from a CDN,
            // include_bootstrap_icons config)
        },
    },
    {
        // admintweaks.js is loaded with extra_requirements_javascript, a classic <script>: an IIFE
        // keeps its top-level code out of the global scope, as the webpack bundle did
        input: { 'admintweaks': 'client/src/js/admintweaks.js' },
        format: 'iife',
    },
];

// Where each emitted asset goes, by type. CSS keeps mix's css/ dir. The FontAwesome webfonts that
// admintweaks.scss points at (client/fonts/...) are copied to fonts/ under their own name, as mix
// did (mix appended a ?hash query for cache busting; Vite has no query option, so the URL is plain).
// Anything else would be an image, mix's images/ dir.
const assetFileNames = (asset) => {
    const name = asset.names?.[0] ?? asset.name ?? '';
    if (name.endsWith('.css')) {
        return 'css/[name][extname]';
    }
    if (/\.(woff2?|ttf|otf|eot)$/.test(name)) {
        return 'fonts/[name][extname]';
    }
    return 'images/[name][extname]';
};

// Keep the CSS ASCII-only, as mix wrote it. The CSS minifier (Lightning CSS) turns an escape such as
// `content: "\e005"` (the jQuery UI dialog close icon in admintweaks.scss) into the raw character,
// so the file would depend on being read as UTF-8 (it has no @charset, and a web server need not
// send a charset for .css). This writes every non-ASCII character back as a CSS escape.
const asciiCss = {
    name: 'admintweaks:ascii-css',
    enforce: 'post',
    generateBundle(options, bundle) {
        for (const file of Object.values(bundle)) {
            if (file.type !== 'asset' || !file.fileName.endsWith('.css') || typeof file.source !== 'string') {
                continue;
            }
            // A space ends the escape only where the next character could be read as part of it
            file.source = file.source.replace(/[^\x00-\x7f]([0-9a-fA-F \t\n])?/gu, (match, next) => {
                const escape = '\\' + match.codePointAt(0).toString(16);
                return next === undefined ? escape : escape + ' ' + next;
            });
        }
    },
};

const config = (part) => ({
    // Everything is configured here; do not pick up a vite.config file
    configFile: false,
    root,
    mode: dev ? 'development' : 'production',
    // Relative URLs in the CSS (../fonts/...), as mix's setResourceRoot('../') gave
    base: './',
    // No public/ dir: nothing besides the build output is copied into client/dist
    publicDir: false,
    plugins: [asciiCss],
    css: {
        preprocessorOptions: {
            scss: {
                // Only what fires is listed; both are removed in Dart Sass 3, the CSS is unaffected today
                // (mix printed the same warnings). `import`: the sources pull their partials in with
                // @import. `global-builtin`: _svg-functions.scss calls str-index()/str-slice()/
                // str-length() instead of the sass:string module's string.index() etc.
                silenceDeprecations: ['import', 'global-builtin'],
                quietDeps: true,
            },
        },
    },
    build: {
        outDir: 'client/dist',
        // client/dist holds every part's output, plus committed files no build writes
        // (the older font formats); a part must never wipe another's files
        emptyOutDir: false,
        // `build` is minified without source maps (as `mix --production` was); `dev` is not
        // minified and inlines its source maps, so no stray .map files land in client/dist
        minify: !dev,
        cssMinify: !dev,
        sourcemap: dev ? 'inline' : false,
        // Keep one CSS file per entry, never inlined into JS
        cssCodeSplit: true,
        // Copy the webfonts as files, never inline them as data: URIs (mix did not inline them either)
        assetsInlineLimit: 0,
        // Browser floor for the JS and (through cssTarget, which follows target) the CSS minifier.
        // Vite 8's default (chrome111/safari16.4) would rewrite media queries to range syntax,
        // `(width>=576px)`, which Safari before 16.4 ignores. This list is Vite 6's default ("modules").
        target: ['es2020', 'edge88', 'firefox78', 'chrome87', 'safari14'],
        watch: watch ? {} : null,
        rolldownOptions: {
            input: part.input,
            // Imports of admin globals stay external and are read from window (see globals above)
            external: Object.keys(globals),
            output: {
                format: part.format ?? 'es',
                globals,
                entryFileNames: 'js/[name].js',
                assetFileNames,
                // Vite drops licence comments when it minifies; keep them where a source has one.
                // webpack moved them to *.LICENSE.txt, this keeps them in the file itself
                comments: { legal: true },
            },
        },
    },
});

for (const part of parts) {
    await build(config(part));
}
