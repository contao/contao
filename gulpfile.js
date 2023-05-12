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

gulp.task('minify-dark-theme-icons', function(cb) {
    pump(
        [
            gulp.src('core-bundle/contao/themes/flexible/icons-dark/*.svg'),
            svgo({
                multipass: true,
                plugins: [{
                    inlineStyles: {
                        onlyMatchedOnce: false
                    }
                }]
            }),
            gulp.dest('core-bundle/contao/themes/flexible/icons-dark')
        ],
        cb
    );
});

gulp.task('watch', function() {
    gulp.watch('core-bundle/contao/themes/flexible/icons/*.svg', gulp.series('minify-theme-icons'));
    gulp.watch('core-bundle/contao/themes/flexible/icons-dark/*.svg', gulp.series('minify-dark-theme-icons'));
});

gulp.task('default', gulp.parallel('minify-theme-icons', 'minify-dark-theme-icons'));
