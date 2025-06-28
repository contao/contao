const Encore = require('@symfony/webpack-encore');

// Core bundle assets
Encore
    .setOutputPath('core-bundle/public/')
    .setPublicPath(Encore.isDevServer() ? '/' : '/bundles/contaocore')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            plugins: {
                'postcss-preset-env': {
                    stage: 2,
                }
            }
        };
    })
    .addEntry('backend', './core-bundle/assets/backend.js')
    .configureDevServerOptions((options) => Object.assign({}, options, {
        static: false,
        hot: true,
        liveReload: true,
        allowedHosts: 'all',
        watchFiles: [
            'core-bundle/assets/styles/*',
            'core-bundle/contao/themes/flexible/styles/*'
        ],
        client: {
            overlay: false
        }
    }))
;

const jsConfig = Encore.getWebpackConfig();

Encore.reset();

// Back end theme "flexible"
Encore
    .setOutputPath('core-bundle/contao/themes/flexible')
    .setPublicPath(Encore.isDevServer() ? '/' : '/system/themes/flexible')
    .setManifestKeyPrefix('')
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enablePostCssLoader()
    .configureCssLoader(config => {
        config.url = false;
    })
    .cleanupOutputBeforeBuild(config => {
        config.keep = /(fonts|icons|styles)\//;
    })
    .addStyleEntry('backend', './core-bundle/contao/themes/flexible/styles/main.pcss')
    .addStyleEntry('confirm', './core-bundle/contao/themes/flexible/styles/pages/confirm.pcss')
    .addStyleEntry('conflict', './core-bundle/contao/themes/flexible/styles/pages/conflict.pcss')
    .addStyleEntry('diff', './core-bundle/contao/themes/flexible/styles/pages/diff.pcss')
    .addStyleEntry('help', './core-bundle/contao/themes/flexible/styles/pages/help.pcss')
    .addStyleEntry('login', './core-bundle/contao/themes/flexible/styles/pages/login.pcss')
    .addStyleEntry('popup', './core-bundle/contao/themes/flexible/styles/pages/popup.pcss')
    .addStyleEntry('tinymce', './core-bundle/contao/themes/flexible/styles/vendors/tinymce/theme/light.pcss')
    .addStyleEntry('tinymce-dark', './core-bundle/contao/themes/flexible/styles/vendors/tinymce/theme/dark.pcss')
;

const themeConfig = Encore.getWebpackConfig();

delete themeConfig.devServer;

module.exports = [jsConfig, themeConfig];
