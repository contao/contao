const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('core-bundle/public/')
    .setPublicPath('/bundles/contaocore')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()
    .enableSourceMaps()
    .enableVersioning()
    .enablePostCssLoader()
    .addEntry('ajax-form', './core-bundle/assets/ajax-form.js')
    .addEntry('backend', './core-bundle/assets/backend.js')
;

const coreConfig = Encore.getWebpackConfig();

module.exports = [coreConfig];
