'use strict';

var gulp = require('gulp'),
    svgo = require('gulp-svgo'),
    pump = require('pump');

gulp.task('minify-theme-icons', function(cb) {
    pump(
        [
            gulp.src('core-bundle/contao/themes/flexible/icons/*.svg'),
            svgo({
                multipass: true,
                plugins: [{
                    inlineStyles: {
                        onlyMatchedOnce: false
                    }
                }]
            }),
            gulp.dest('core-bundle/contao/themes/flexible/icons')
        ],
        cb
    );
});

gulp.task('watch', function() {
    gulp.watch('core-bundle/contao/themes/flexible/icons/*.svg', gulp.series('minify-theme-icons'));
});

gulp.task('default', gulp.parallel('minify-theme-icons'));
