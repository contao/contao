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

const coreConfig = Encore.getWebpackConfig();

module.exports = [coreConfig];
