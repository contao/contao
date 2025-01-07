export default {
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
};
