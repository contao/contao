module.exports = {
    plugins: [
        require('postcss-nested'),
        require('autoprefixer'),
        require('postcss-preset-env')({
             stage: 4,
        }),
    ]
}
