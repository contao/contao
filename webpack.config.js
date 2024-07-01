const Encore = require('@symfony/webpack-encore');
const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin');

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

if (!Encore.isDev()) {
    Encore.reset();

    Encore
        .setOutputPath('core-bundle/contao/themes/flexible/icons')
        .setPublicPath('/system/themes/flexible/icons')
        .setManifestKeyPrefix('')
        .disableSingleRuntimeChunk()
        .addPlugin(new ImageMinimizerPlugin({
            minimizer: {
                implementation: ImageMinimizerPlugin.svgoMinify,
                options: {
                    encodeOptions: {
                        multipass: true,
                        plugins: [{
                            name: 'preset-default',
                            params: {
                                overrides: {
                                    inlineStyles: {
                                        onlyMatchedOnce: false,
                                    },
                                    convertPathData: {
                                        noSpaceAfterFlags: true,
                                    },
                                },
                            },
                        }],
                    },
                },
            },
        }))
        .copyFiles({
            from: './core-bundle/contao/themes/flexible/icons',
            to: '[name].[ext]',
            pattern: /\.svg$/,
        })
    ;

    const iconConfig = Encore.getWebpackConfig();

    module.exports.push(iconConfig)
}
