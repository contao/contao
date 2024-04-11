export default {
    multipass: true,
    plugins: [{
        name: 'preset-default',
        params: {
            overrides: {
                convertPathData: {
                    noSpaceAfterFlags: true,
                },
                inlineStyles: {
                    onlyMatchedOnce: false,
                },
            },
        },
    }],
};
