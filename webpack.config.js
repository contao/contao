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
    .addEntry('backend', './core-bundle/assets/backend.js')
;

const jsConfig = Encore.getWebpackConfig();

Encore.reset();

// Back end theme "flexible"
Encore
    .setOutputPath('core-bundle/contao/themes/flexible')
    .setPublicPath('/system/themes/flexible')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild(['*.css', '*.json', '*.map'])
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureCssLoader(config => {
        config.url = false;
    })
    .enableSassLoader()
    .addStyleEntry('backend', './core-bundle/contao/themes/flexible/styles/layout/_main.scss')
    .addStyleEntry('confirm', './core-bundle/contao/themes/flexible/styles/pages/_confirm.scss')
    .addStyleEntry('conflict', './core-bundle/contao/themes/flexible/styles/pages/_conflict.scss')
    .addStyleEntry('diff', './core-bundle/contao/themes/flexible/styles/pages/_diff.scss')
    .addStyleEntry('help', './core-bundle/contao/themes/flexible/styles/pages/_help.scss')
    .addStyleEntry('login', './core-bundle/contao/themes/flexible/styles/pages/_login.scss')
    .addStyleEntry('popup', './core-bundle/contao/themes/flexible/styles/pages/_popup.scss')
    .addStyleEntry('tinymce', './core-bundle/contao/themes/flexible/styles/components/_tinymce.scss')
    .addStyleEntry('tinymce-dark', './core-bundle/contao/themes/flexible/styles/components/_tinymce-dark.scss')
;

const themeConfig = Encore.getWebpackConfig();

module.exports = [jsConfig, themeConfig];
