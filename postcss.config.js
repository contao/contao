module.exports = {
    plugins: {
        'postcss-preset-env': {
            stage: 2,
        },
        'postcss-url': {
            url: (asset) => {
                if (asset.url[0] === '/') {
                  return `/system/themes/flexible/${asset.url}`;
                }
                return asset.url;
            }
        }
    }
}
