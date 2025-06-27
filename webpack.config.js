const Encore = require('@symfony/webpack-encore');

// Core bundle assets
Encore
    .setOutputPath('core-bundle/public/')
    .setPublicPath('/bundles/contaocore')
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
    .addEntry('passkey_login', './core-bundle/assets/passkey_login.js')
;

const jsConfig = Encore.getWebpackConfig();

Encore.reset();

// Back end theme "flexible"
Encore
    .setOutputPath('core-bundle/contao/themes/flexible')
    .setPublicPath('/system/themes/flexible')
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

module.exports = [jsConfig, themeConfig];
