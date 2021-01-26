const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('../src/Resources/public/theme')
    .setPublicPath('/bundles/contaocore/theme')
    .setManifestKeyPrefix('bundles/contaocore/theme')

    .cleanupOutputBeforeBuild()
    .enableSourceMaps(false)
    .enableVersioning(false)
    .disableSingleRuntimeChunk()
    .enablePostCssLoader()

    .addEntry('app', './js/app.js')
    .copyFiles({
        from: './images/',
        to: 'images/[path][name].[ext]',
    })
;

module.exports = Encore.getWebpackConfig();
