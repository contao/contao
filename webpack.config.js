const Encore = require('@symfony/webpack-encore');
const path = require('path');
const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin');

// Core bundle assets
Encore
    .setOutputPath('core-bundle/public/')
    .setPublicPath(Encore.isDevServer() ? '/core-bundle/public' : '/bundles/contaocore')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enablePostCssLoader(options => {
        options.postcssOptions = {
            plugins: {
                'postcss-preset-env': {
                    stage: 2,
                }
            }
        };
    })
    .cleanupOutputBeforeBuild(options => {
        options.keep = /icons\//;
    })
    .addEntry('backend', './core-bundle/assets/backend.js')
    .addEntry('navigation', './core-bundle/assets/navigation.js')
    .addEntry('passkey_login', './core-bundle/assets/passkey_login.js')
    .addEntry('passkey_create', './core-bundle/assets/passkey_create.js')
    .addStyleEntry('login', './core-bundle/assets/styles/login.pcss')
    .addStyleEntry('tinymce', './core-bundle/assets/styles/vendors/tinymce/theme/light.pcss')
    .addStyleEntry('tinymce-dark', './core-bundle/assets/styles/vendors/tinymce/theme/dark.pcss')
    .configureDevServerOptions(options => {
        options.server = {
            type: 'https',
            options: {
                pfx: path.join(process.env.HOME, '.symfony5/certs/default.p12')
            }
        };
        options.allowedHosts = 'all';
    })
;

const jsConfig = Encore.getWebpackConfig();

Encore.reset();

// Back end icons
Encore
    .setOutputPath('core-bundle/public/icons')
    .setPublicPath(Encore.isDevServer() ? '/core-bundle/public/icons' : '/bundles/contaocore/icons')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild()
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
        from: './core-bundle/assets/icons',
        to: '[name].[hash:8].[ext]',
        pattern: /\.svg$/,
    })
    .configureDevServerOptions(options => {
        options.server = {
            type: 'https',
            options: {
                pfx: path.join(process.env.HOME, '.symfony5/certs/default.p12')
            }
        };
        options.allowedHosts = 'all';
    })
;

const iconConfig = Encore.getWebpackConfig();

delete iconConfig.devServer;

module.exports = [jsConfig, iconConfig];
