const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('core-bundle/public/')
    .setPublicPath('/bundles/contaocore')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()
    .enableSourceMaps()
    .enableVersioning()
    .addEntry('backend', './core-bundle/assets/backend.js')
;

const jsConfig = Encore.getWebpackConfig();

Encore.reset();

Encore
    .setOutputPath('core-bundle/contao/themes/flexible')
    .setPublicPath('/system/themes/flexible')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild(['*.css', '*.json', '*.map'])
    .disableSingleRuntimeChunk()
    .enableSourceMaps()
    .enableVersioning()
    .configureCssLoader(config => {
        config.url = false;
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
