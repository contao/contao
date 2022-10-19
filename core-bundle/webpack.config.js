const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/')
    .setPublicPath('/bundles/contaocore')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild(['**/*', '!core.**', '!mootao.**'])
    .disableSingleRuntimeChunk()

    .enableSourceMaps()
    .enableVersioning()

    .addEntry('backend', './assets/backend.js')
    .enableStimulusBridge('./assets/controllers.json')
;

module.exports = Encore.getWebpackConfig();
