/**
 * WEBPACK
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 *   1 - vendor
 *   2 - front
 *   3 - gdpr
 *   4 - admin
 *   5 - security
 *   6 - module.exports
 */

const Encore = require('@symfony/webpack-encore');
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

const path = require('path');
const glob = require('glob');

const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const {CleanWebpackPlugin} = require('clean-webpack-plugin');
const {PurgeCSSPlugin} = require('purgecss-webpack-plugin');

function safeList() {
    let patterns = [
        'html', 'body', 'img', 'svg', 'picture', 'sup', 'a', 'button', 'address', 'badge', 'ended', 'no-backdrop', 'no-webp', 'support-webp', 'standardize-medias', 'as-btn',
        /start$/, /end$/, /next$/, /prev$/, /open$/, /text$/, /modal$/, /card$/, /carousel$/, /tooltip$/, /collapsed$/, /collapsing$/, /navigation$/,
        /img$/, /svg$/, /active$/, /show$/, /link$/, /address$/,
        /-body/, /-footer/, /-style/, /m-/, /mx-/, /my-/, /mb-/, /mt-/, /ms-/, /me-/, /p-/, /px-/, /py-/, /pb-/, /pt-/, /ps-/, /pe-/, /fw-/, /fz-/, /-none/, /h-0/,
        /offset-/, /h-100/, /d-/, /align-/, /-align/, /flex-/, /list-/, /justify-/, /fixed-/, /link-/, /display-/, /opactity-/, /have-/,
        /screen-/, /ribbon-/, /alert-/, /badge-/, /-view-body/, /text-/, /zone-/, /custom-/, /col-/, /block-/, /level-/, /ff-/, /card-/,
        /-block/, /order-/, /btn-/, /gdt-/, /bg-/, /modal-/, /tooltip-/, /card-/, /cta-/, /carousel-/, /overlay-/, /-overlay/, /as-scroll/,
        /address/, /container/, /body/, /description/, /introduction/, /sr-only/,
        /datepicker-/, /days/, /days-/, /dow/, /selected/, /autofill/, /focus/, /choices__/, /splide_/,
        /overflow-initial/, /parallax-window/, /full-screen/, /mobile-first/, /full-size/, /aos/, /lax/, /as-newscast-teaser/, /animation/, /aspect-ratio/, /large-file-container/, /fa-spin/, /shadow-box/, /shadow-left/, /shadow-right/,
    ];
    return {
        standard: patterns
    }
}

function blockList() {
    return [
        'code', 'lead'
    ];
}

const enableNotification = false;
const enableSourceMaps = !Encore.isProduction();
const enableVersioning = true; // else Encore.isProduction()
const enableIntegrity = true; // else Encore.isProduction()
const target = 'web';
const cache = Encore.isProduction();
const parallelism = 4;
const concatenateModules = Encore.isProduction();
const providedExports = Encore.isProduction();
const usedExports = Encore.isProduction();
const removeEmptyChunks = true; // else Encore.isProduction()
const mergeDuplicateChunks = true; // else Encore.isProduction()
const sideEffects = true; // else Encore.isProduction()
const splitChunks = {
    chunks: 'async',
    minSize: 20000,
    maxSize: 250000,
    maxAsyncRequests: 30,
    maxInitialRequests: 30,
};
const minimize = Encore.isProduction();

/** 1 - vendor */

Encore.setOutputPath('public/build/vendor')
    .setPublicPath('/build/vendor')
    .addEntry('async', './assets/js/vendor/async.js')
    .addEntry('browsers', './assets/js/vendor/browsers.js')
    .addEntry('vendor-js', './assets/js/vendor/vendor.js')
    .addEntry('first-paint', './assets/js/vendor/first-paint.js')
    .addEntry('lazy-load', './assets/js/vendor/components/lazy-load.js')
    .addStyleEntry('vendor-css', ['./assets/scss/vendor/vendor.scss'])
    .addStyleEntry('debug', ['./assets/scss/vendor/debug.scss'])
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(enableSourceMaps)
    .enableVersioning(enableVersioning)
    .enableIntegrityHashes(enableIntegrity)
    .autoProvideVariables({
        moment: 'moment'
    })
    .copyFiles({
        from: './assets/medias/images/vendor',
        to: 'images/[path][name].[hash:8].[ext]'
    })
    .copyFiles({
        from: './assets/lib/icons/animated',
        to: 'icons/animated/[path][name].[ext]'
    })
    .copyFiles({
        from: './assets/js/vendor/plugins/i18n',
        to: 'i18n/[path][name].[ext]'
    })
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38'
    })
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            config: path.resolve(__dirname, "postcss.config.js")
        };
    })
    .configureImageRule({
        type: 'asset',
        maxSize: 8 * 1024, /** 8 kb - the default is 8kb */
    })
    .configureFontRule({
        type: 'asset',
        maxSize: 8 * 1024
    })
    .splitEntryChunks()
    .configureSplitChunks(function (splitChunks) {
        splitChunks.chunks = 'all'; // Tous les types de chunks
        splitChunks.minSize = 20000; // Taille minimale d'un chunk
        splitChunks.maxSize = 250000; // Taille maximale d'un chunk
        splitChunks.maxAsyncRequests = 30;
        splitChunks.maxInitialRequests = 30;
        splitChunks.enforceSizeThreshold = 50000;
    })
    .addPlugin(new CleanWebpackPlugin())
    .enableSingleRuntimeChunk()
    .enableSassLoader()
    .autoProvidejQuery();

if (enableNotification) {
    Encore.enableBuildNotifications();
}

const vendor = Encore.getWebpackConfig();
vendor.output.trustedTypes = {
    policyName: 'webpack-policy', // nom de la policy TT utilisée par le runtime
    onPolicyCreationFailure: 'continue', // évite de casser en cas d’échec (ex: vieux navigateurs)
};
vendor.name = 'vendor';
vendor.target = target;
vendor.cache = cache;
vendor.parallelism = parallelism;
vendor.optimization.concatenateModules = concatenateModules;
vendor.optimization.providedExports = providedExports;
vendor.optimization.usedExports = usedExports;
vendor.optimization.removeEmptyChunks = removeEmptyChunks;
vendor.optimization.mergeDuplicateChunks = mergeDuplicateChunks;
vendor.optimization.sideEffects = sideEffects;
vendor.optimization.splitChunks = splitChunks;
vendor.optimization.minimize = minimize;
vendor.resolve.extensions.push('json');
if (vendor.optimization && vendor.optimization.minimizer) {
    vendor.optimization.minimizer.push(new CssMinimizerPlugin());
}

/** 2 - front */

Encore.reset();

Encore.setOutputPath('public/build/front/default')
    .setPublicPath('/build/front/default')
    .addEntry('front-default-vendor', './assets/js/front/default/vendor.js')
    .addEntry('front-default-home', './assets/js/front/default/templates/home.js')
    .addEntry('front-default-cms', './assets/js/front/default/templates/cms.js')
    .addEntry('front-default-legacy', './assets/js/front/default/templates/legacy.js')
    .addEntry('front-default-security', './assets/js/front/default/templates/security.js')
    .addEntry('front-default-security-back', './assets/js/front/default/templates/security-back.js')
    .addEntry('front-default-catalog', './assets/js/front/default/templates/catalog.js')
    .addEntry('front-default-newscast', './assets/js/front/default/templates/newscast.js')
    .addEntry('front-default-build', './assets/js/front/default/templates/build.js')
    .addEntry('front-default-identification', './assets/js/front/default/templates/identification.js')
    .addEntry('front-default-switcher', './assets/js/front/default/templates/switcher.js')
    .addEntry('front-default-error', './assets/js/front/default/templates/error.js')
    .addEntry('front-default-gdpr', './assets/js/front/default/gdpr.js')
    .addEntry('front-default-matomo', './assets/js/gdpr/matomo.js')
    .addStyleEntry('front-default-vendor-mobile', ['./assets/scss/front/default/vendor-mobile.scss'])
    .addStyleEntry('front-default-vendor-desktop', ['./assets/scss/front/default/vendor-desktop.scss'])
    .addStyleEntry('front-default-noscript', ['./assets/scss/front/default/noscript.scss'])
    .addStyleEntry('front-default-print', ['./assets/scss/front/default/print.scss'])
    .addStyleEntry('front-default-fonts', ['./assets/scss/front/default/fonts.scss'])
    .cleanupOutputBeforeBuild()
    .enableVersioning(enableVersioning)
    .enableSourceMaps(enableSourceMaps)
    .enableIntegrityHashes(enableIntegrity)
    .autoProvideVariables({
        moment: 'moment'
    })
    .copyFiles({
        from: './assets/medias/images/front/default',
        to: 'images/[path][name].[hash:8].[ext]'
    })
    .copyFiles({
        from: './assets/medias/movies',
        to: 'movies/[path][name].[hash:8].[ext]'
    })
    .configureBabel(function (babelConfig) {
        babelConfig.presets.push('@babel/preset-flow');
    }, {})
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
        config.targets = { esmodules: true };
    })
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            config: path.resolve(__dirname, "postcss.config.js")
        };
    })
    .configureImageRule({
        type: 'asset',
        maxSize: 8 * 1024, /** 8 kb - the default is 8kb */
    })
    .configureFontRule({
        type: 'asset',
        maxSize: 8 * 1024
    })
    .splitEntryChunks()
    .enableStimulusBridge('./assets/js/front/default/controllers.json')
    .configureSplitChunks(function (splitChunks) {
        splitChunks.chunks = 'all'; // Tous les types de chunks
        splitChunks.minSize = 20000; // Augmenté pour éviter trop de petits fichiers
        splitChunks.maxSize = 250000;
        splitChunks.maxAsyncRequests = 30; // Augmenté pour le parallélisme HTTP/2+
        splitChunks.maxInitialRequests = 30;
        splitChunks.enforceSizeThreshold = 50000;
    })
    .addPlugin(new CleanWebpackPlugin())
    .addPlugin(new PurgeCSSPlugin({
        paths: glob.sync(
            `${path.join(__dirname, 'templates')}/{front/default,core,components,gdpr}/**/*.html.twig`, {nodir: true}
        ),
        safelist: safeList,
        blocklist: blockList,
    }))
    .disableSingleRuntimeChunk()
    .enableSassLoader();

if (enableNotification) {
    Encore.enableBuildNotifications();
}

const front_default = Encore.getWebpackConfig();
front_default.output.trustedTypes = {
    policyName: 'webpack-policy', // nom de la policy TT utilisée par le runtime
    onPolicyCreationFailure: 'continue', // évite de casser en cas d’échec (ex: vieux navigateurs)
};
front_default.name = 'front_default';
front_default.target = target;
front_default.cache = cache;
front_default.parallelism = parallelism;
front_default.optimization.concatenateModules = concatenateModules;
front_default.optimization.providedExports = providedExports;
front_default.optimization.usedExports = usedExports;
front_default.optimization.removeEmptyChunks = removeEmptyChunks;
front_default.optimization.mergeDuplicateChunks = mergeDuplicateChunks;
front_default.optimization.sideEffects = sideEffects;
front_default.optimization.splitChunks = splitChunks;
front_default.optimization.minimize = minimize;
front_default.resolve.extensions.push('json');
if (front_default.optimization && front_default.optimization.minimizer) {
    front_default.optimization.minimizer.push(new CssMinimizerPlugin());
}

/** 3 - gdpr */

Encore.reset();

Encore.setOutputPath('public/build/gdpr')
    .setPublicPath('/build/gdpr')
    .addEntry('gdpr', './assets/js/gdpr/vendor.js')
    .addEntry('google-tag-manager', './assets/js/gdpr/injectors/google-tag-manager.js')
    .addEntry('google-analytics', './assets/js/gdpr/injectors/google-analytics.js')
    .addEntry('facebook-pixel', './assets/js/gdpr/injectors/facebook-pixel.js')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(enableSourceMaps)
    .enableVersioning(enableVersioning)
    .enableIntegrityHashes(enableIntegrity)
    .configureBabel(function (babelConfig) {
        babelConfig.presets.push('@babel/preset-flow');
    }, {})
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38'
    })
    .copyFiles({
        from: './assets/medias/images/gdpr',
        to: 'images/[path][name].[hash:8].[ext]'
    })
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            config: path.resolve(__dirname, "postcss.config.js")
        };
    })
    .configureImageRule({
        type: 'asset',
        maxSize: 8 * 1024, /** 8 kb - the default is 8kb */
    })
    .configureFontRule({
        type: 'asset',
        maxSize: 8 * 1024
    })
    .splitEntryChunks()
    .configureSplitChunks(function (splitChunks) {
        splitChunks.chunks = 'all'; // Tous les types de chunks
        splitChunks.minSize = 20000; // Taille minimale d'un chunk
        splitChunks.maxSize = 250000; // Taille maximale d'un chunk
        splitChunks.maxAsyncRequests = 30;
        splitChunks.maxInitialRequests = 30;
        splitChunks.enforceSizeThreshold = 50000;
    })
    .addPlugin(new CleanWebpackPlugin())
    .enableSingleRuntimeChunk()
    .enableSassLoader();

if (enableNotification) {
    Encore.enableBuildNotifications();
}

const gdpr = Encore.getWebpackConfig();
gdpr.output.trustedTypes = {
    policyName: 'webpack-policy', // nom de la policy TT utilisée par le runtime
    onPolicyCreationFailure: 'continue', // évite de casser en cas d’échec (ex: vieux navigateurs)
};
gdpr.name = 'gdpr';
gdpr.target = target;
gdpr.cache = cache;
gdpr.parallelism = parallelism;
gdpr.optimization.concatenateModules = concatenateModules;
gdpr.optimization.providedExports = providedExports;
gdpr.optimization.usedExports = usedExports;
gdpr.optimization.removeEmptyChunks = removeEmptyChunks;
gdpr.optimization.mergeDuplicateChunks = mergeDuplicateChunks;
gdpr.optimization.sideEffects = sideEffects;
gdpr.optimization.splitChunks = splitChunks;
gdpr.optimization.minimize = minimize;
gdpr.resolve.extensions.push('json');
if (gdpr.optimization && gdpr.optimization.minimizer) {
    gdpr.optimization.minimizer.push(new CssMinimizerPlugin());
}

/** 4 - admin */

Encore.reset();

Encore.setOutputPath('public/build/admin')
    .setPublicPath('/build/admin')
    .addEntry('admin-vendor-default', './assets/js/admin/vendor-default.js')
    .addEntry('admin-vendor-clouds', './assets/js/admin/vendor-clouds.js')
    .addEntry('admin-vendor-dark', './assets/js/admin/vendor-dark.js')
    .addEntry('admin-seo', './assets/js/admin/pages/seo.js')
    .addEntry('admin-medias-library', './assets/js/admin/media/library.js')
    .addEntry('admin-medias-cropper', './assets/js/admin/media/cropper.js')
    .addEntry('admin-icons-library', './assets/js/admin/pages/icons-library.js')
    .addEntry('admin-translation', './assets/js/admin/pages/translation.js')
    .addEntry('admin-table', './assets/js/admin/pages/table.js')
    .addEntry('admin-menu', './assets/js/admin/pages/menu.js')
    .addEntry('admin-edit-in-tab', './assets/js/admin/form/edit-in-tab.js')
    .addEntry('admin-agenda', './assets/js/admin/pages/agenda.js')
    .addEntry('admin-user-profile', './assets/js/admin/pages/user-profile.js')
    .addEntry('admin-google-analytics', './assets/js/admin/pages/analytics/google-analytics.js')
    .addEntry('admin-analytics', './assets/js/admin/pages/analytics/analytics.js')
    .addEntry('admin-development', './assets/js/admin/pages/development.js')
    .addEntry('admin-website', './assets/js/admin/pages/website.js')
    .addEntry('admin-dashboard', './assets/js/admin/pages/dashboard.js')
    .addStyleEntry('admin-extensions', ['./assets/scss/admin/pages/extensions.scss'])
    .addStyleEntry('admin-error', ['./assets/scss/admin/pages/error.scss'])
    .addStyleEntry('admin-fonts', ['./assets/scss/admin/fonts.scss'])
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(enableSourceMaps)
    .enableVersioning(enableVersioning)
    .enableIntegrityHashes(enableIntegrity)
    .autoProvideVariables({
        moment: 'moment'
    })
    .copyFiles({
        from: './assets/medias/images/admin',
        to: 'images/theme/[path][name].[hash:8].[ext]'
    })
    .copyFiles({
        from: './assets/medias/docs/admin',
        to: 'docs/[path][name].[ext]'
    })
    .configureBabel(function (babelConfig) {
        babelConfig.presets.push('@babel/preset-flow');
    }, {})
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38'
    })
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            config: path.resolve(__dirname, "postcss.config.js")
        };
    })
    .configureImageRule({
        type: 'asset',
        maxSize: 8 * 1024, /** 8 kb - the default is 8kb */
    })
    .configureFontRule({
        type: 'asset',
        maxSize: 8 * 1024
    })
    .splitEntryChunks()
    .enableStimulusBridge('./assets/js/admin/controllers.json')
    .configureSplitChunks(function (splitChunks) {
        splitChunks.chunks = 'all'; // Tous les types de chunks
        splitChunks.minSize = 20000; // Taille minimale d'un chunk
        splitChunks.maxSize = 250000; // Taille maximale d'un chunk
        splitChunks.maxAsyncRequests = 30;
        splitChunks.maxInitialRequests = 30;
        splitChunks.enforceSizeThreshold = 50000;
    })
    .addPlugin(new CleanWebpackPlugin())
    .enableSingleRuntimeChunk()
    .enableSassLoader()
    .autoProvidejQuery();

if (enableNotification) {
    Encore.enableBuildNotifications();
}

const admin = Encore.getWebpackConfig();
admin.output.trustedTypes = {
    policyName: 'webpack-policy', // nom de la policy TT utilisée par le runtime
    onPolicyCreationFailure: 'continue', // évite de casser en cas d’échec (ex: vieux navigateurs)
};
admin.name = 'admin';
admin.target = target;
admin.cache = cache;
admin.parallelism = parallelism;
admin.optimization.concatenateModules = concatenateModules;
admin.optimization.providedExports = providedExports;
admin.optimization.usedExports = usedExports;
admin.optimization.removeEmptyChunks = removeEmptyChunks;
admin.optimization.mergeDuplicateChunks = mergeDuplicateChunks;
admin.optimization.sideEffects = sideEffects;
admin.optimization.splitChunks = splitChunks;
admin.optimization.minimize = minimize;
admin.resolve.extensions.push('json');
if (admin.optimization && admin.optimization.minimizer) {
    admin.optimization.minimizer.push(new CssMinimizerPlugin());
}

/** 5 - security */

Encore.reset();

Encore.setOutputPath('public/build/security')
    .setPublicPath('/build/security')
    .addEntry('security', './assets/js/security/vendor.js')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(enableSourceMaps)
    .enableVersioning(enableVersioning)
    .enableIntegrityHashes(enableIntegrity)
    .copyFiles({
        from: './assets/medias/images/security',
        to: 'images/[path][name].[hash:8].[ext]'
    })
    .configureBabel(function (babelConfig) {
        babelConfig.presets.push('@babel/preset-flow');
    }, {})
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38'
    })
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            config: path.resolve(__dirname, "postcss.config.js")
        };
    })
    .configureImageRule({
        type: 'asset',
        maxSize: 8 * 1024, /** 8 kb - the default is 8kb */
    })
    .configureFontRule({
        type: 'asset',
        maxSize: 8 * 1024
    })
    .splitEntryChunks()
    .configureSplitChunks(function (splitChunks) {
        splitChunks.chunks = 'all'; // Tous les types de chunks
        splitChunks.minSize = 20000; // Taille minimale d'un chunk
        splitChunks.maxSize = 250000; // Taille maximale d'un chunk
        splitChunks.maxAsyncRequests = 30;
        splitChunks.maxInitialRequests = 30;
        splitChunks.enforceSizeThreshold = 50000;
    })
    .addPlugin(new CleanWebpackPlugin())
    .enableSingleRuntimeChunk()
    .enableSassLoader();

if (enableNotification) {
    Encore.enableBuildNotifications();
}

const security = Encore.getWebpackConfig();
security.output.trustedTypes = {
    policyName: 'webpack-policy', // nom de la policy TT utilisée par le runtime
    onPolicyCreationFailure: 'continue', // évite de casser en cas d’échec (ex: vieux navigateurs)
};
security.name = 'security';
security.target = target;
security.cache = cache;
security.parallelism = parallelism;
security.optimization.concatenateModules = concatenateModules;
security.optimization.providedExports = providedExports;
security.optimization.usedExports = usedExports;
security.optimization.removeEmptyChunks = removeEmptyChunks;
security.optimization.mergeDuplicateChunks = mergeDuplicateChunks;
security.optimization.sideEffects = sideEffects;
security.optimization.splitChunks = splitChunks;
security.optimization.minimize = minimize;
security.resolve.extensions.push('json');
if (security.optimization && security.optimization.minimizer) {
    security.optimization.minimizer.push(new CssMinimizerPlugin());
}

/** 6 - module.exports */
module.exports = [vendor, front_default, gdpr, admin, security];