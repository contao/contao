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
    .cleanupOutputBeforeBuild(config => {
        config.keep = /(fonts|icons|styles)\//;
    })
    .cleanupOutputBeforeBuild(config => {
        config.keep = /(fonts|icons|styles)\//;
    })
    .addEntry('backend', './core-bundle/contao/themes/flexible/styles/_main.pcss')
    .addEntry('confirm', './core-bundle/contao/themes/flexible/styles/pages/_confirm.pcss')
    .addEntry('conflict', './core-bundle/contao/themes/flexible/styles/pages/_conflict.pcss')
    .addEntry('diff', './core-bundle/contao/themes/flexible/styles/pages/_diff.pcss')
    .addEntry('help', './core-bundle/contao/themes/flexible/styles/pages/_help.pcss')
    .addEntry('login', './core-bundle/contao/themes/flexible/styles/pages/_login.pcss')
    .addEntry('popup', './core-bundle/contao/themes/flexible/styles/pages/_popup.pcss')
    .addEntry('tinymce', './core-bundle/contao/themes/flexible/styles/components/_tinymce.pcss')
    .addEntry('tinymce-dark', './core-bundle/contao/themes/flexible/styles/components/_tinymce-dark.pcss')
;

const themeConfig = Encore.getWebpackConfig();

module.exports = [jsConfig, themeConfig];
