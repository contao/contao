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
    .enableTypeScriptLoader()
    .enableForkedTypeScriptTypesChecking()

    .addEntry('app', './js/app.ts')

    // Disable versioning for git-tracked files
    .configureImageRule({
        filename: 'images/[name][ext]'
    })
    .copyFiles({
        from: './images/',
        to: 'images/[path][name].[ext]',
    })
;

module.exports = Encore.getWebpackConfig();
