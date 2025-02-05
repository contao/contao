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
    .enablePostCssLoader()
    .addEntry('backend', './core-bundle/assets/backend.js')
    .addEntry('navigation', './core-bundle/assets/navigation.js')
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
    .configureCssLoader(config => {
        config.url = false;
    })
    .cleanupOutputBeforeBuild(config => {
        config.keep = /(fonts|icons|styles)\//;
    })
    .addStyleEntry('backend', './core-bundle/contao/themes/flexible/styles/main.css')
    .addStyleEntry('confirm', './core-bundle/contao/themes/flexible/styles/confirm.css')
    .addStyleEntry('conflict', './core-bundle/contao/themes/flexible/styles/conflict.css')
    .addStyleEntry('diff', './core-bundle/contao/themes/flexible/styles/diff.css')
    .addStyleEntry('help', './core-bundle/contao/themes/flexible/styles/help.css')
    .addStyleEntry('login', './core-bundle/contao/themes/flexible/styles/login.css')
    .addStyleEntry('popup', './core-bundle/contao/themes/flexible/styles/popup.css')
    .addStyleEntry('tinymce', './core-bundle/contao/themes/flexible/styles/tinymce.css')
    .addStyleEntry('tinymce-dark', './core-bundle/contao/themes/flexible/styles/tinymce-dark.css')
;

const themeConfig = Encore.getWebpackConfig();

module.exports = [jsConfig, themeConfig];
