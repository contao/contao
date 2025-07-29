const Encore = require('@symfony/webpack-encore');
const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin');

// Core bundle assets
Encore
    .setOutputPath('core-bundle/public/')
    .setPublicPath(Encore.isDevServer() ? '/' : '/bundles/contaocore')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            plugins: {
                'postcss-preset-env': {
                    stage: 2,
                }
            }
        };
    })
    .addEntry('contao-backend', './core-bundle/assets/backend.js')
    .addEntry('navigation', './core-bundle/assets/navigation.js')
    .addEntry('passkey_login', './core-bundle/assets/passkey_login.js')
    .addEntry('passkey_create', './core-bundle/assets/passkey_create.js')
    .configureDevServerOptions((options) => Object.assign({}, options, {
        static: [
            {
                directory: 'core-bundle/contao/themes/flexible/icons',
                publicPath: '/icons',
            },
            {
                directory: 'core-bundle/contao/themes/flexible/fonts',
                publicPath: '/fonts',
            },
        ],
        hot: true,
        liveReload: true,
        allowedHosts: 'all',
        watchFiles: [
            'core-bundle/assets/styles/**/*',
            'core-bundle/contao/themes/flexible/styles/**/*'
        ],
        client: {
            overlay: false
        }
    }))
;

const jsConfig = Encore.getWebpackConfig();

Encore.reset();

// Back end theme "flexible"
Encore
    .setOutputPath('core-bundle/contao/themes/flexible')
    .setPublicPath(Encore.isDevServer() ? '/' : '/system/themes/flexible')
    .setManifestKeyPrefix('')
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            plugins: {
                'postcss-preset-env': {
                    stage: 2,
                }
            }
        };
    })
    .configureCssLoader(config => {
        config.url = false;
    })
    .cleanupOutputBeforeBuild(config => {
        config.keep = /(fonts|icons|styles)\//;
    })
    .addStyleEntry('backend', './core-bundle/contao/themes/flexible/styles/main.pcss')
    .addStyleEntry('confirm', './core-bundle/contao/themes/flexible/styles/pages/confirm.pcss')
    .addStyleEntry('conflict', './core-bundle/contao/themes/flexible/styles/pages/conflict.pcss')
    .addStyleEntry('diff', './core-bundle/contao/themes/flexible/styles/pages/diff.pcss')
    .addStyleEntry('help', './core-bundle/contao/themes/flexible/styles/pages/help.pcss')
    .addStyleEntry('login', './core-bundle/contao/themes/flexible/styles/pages/login.pcss')
    .addStyleEntry('popup', './core-bundle/contao/themes/flexible/styles/pages/popup.pcss')
    .addStyleEntry('tinymce', './core-bundle/contao/themes/flexible/styles/vendors/tinymce/theme/light.pcss')
    .addStyleEntry('tinymce-dark', './core-bundle/contao/themes/flexible/styles/vendors/tinymce/theme/dark.pcss')
;

const themeConfig = Encore.getWebpackConfig();

Encore.reset();

// Back end icons
Encore
    .setOutputPath('core-bundle/contao/themes/flexible/icons')
    .setPublicPath(Encore.isDevServer() ? '/' : '/system/themes/flexible/icons')
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
    .configureWatchOptions(watchOptions => {
        // Since we overwrite the sources, we need to prevent an endless loop.
        watchOptions.ignored = ['**/core-bundle/contao/themes/flexible/icons'];
    })
;

const iconConfig = Encore.getWebpackConfig();

delete themeConfig.devServer;
delete iconConfig.devServer;

module.exports = [jsConfig, themeConfig, iconConfig];
