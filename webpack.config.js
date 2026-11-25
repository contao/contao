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
    .addEntry('backend', './core-bundle/assets/backend.js')
    .addEntry('navigation', './core-bundle/assets/navigation.js')
    .addEntry('passkey_login', './core-bundle/assets/passkey_login.js')
    .addEntry('passkey_create', './core-bundle/assets/passkey_create.js')
    .addStyleEntry('login', './core-bundle/assets/styles/login.pcss')
    .addStyleEntry('hugerte', './core-bundle/assets/styles/vendors/hugerte/theme/light.pcss')
    .addStyleEntry('hugerte-dark', './core-bundle/assets/styles/vendors/hugerte/theme/dark.pcss')
    .addPlugin(new ImageMinimizerPlugin({
        test: /(^|\/)icons\//,
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
    .configureImageRule({}, rule => {
        rule.oneOf.unshift({
            include: path.resolve(__dirname, 'core-bundle/assets/icons'),
            type: 'asset/resource',
            generator: {
                filename: 'icons/[name].[hash:8][ext]',
            },
        });
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

module.exports = Encore.getWebpackConfig();
