const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/')
    .setPublicPath('/bundles/contaocore')
    .setManifestKeyPrefix('')
    // .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()

    .enableSourceMaps()
    .enableVersioning()

    .addEntry('app', './assets/app.js')
    .enableStimulusBridge('./assets/controllers.json')
;

module.exports = Encore.getWebpackConfig();
