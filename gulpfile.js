'use strict';

var gulp = require('gulp'),
    csso = require('gulp-csso'),
    ignore = require('gulp-ignore'),
    rename = require('gulp-rename'),
    svgo = require('gulp-svgo'),
    uglify = require('gulp-uglify-es').default,
    pump = require('pump');

gulp.task('minify-theme-js', function (cb) {
    pump(
        [
            gulp.src('core-bundle/contao/themes/flexible/*.js'),
            ignore.exclude('*.min.js'),
            uglify({
                output: {
                    comments: false
                }
            }),
            rename({
                suffix: '.min'
            }),
            gulp.dest('core-bundle/contao/themes/flexible')
        ],
        cb
    );
});

gulp.task('minify-theme-css', function (cb) {
    pump(
        [
            gulp.src('core-bundle/contao/themes/flexible/*.css'),
            ignore.exclude('*.min.css'),
            csso({
                comments: false,
                restructure: false
            }),
            rename({
                suffix: '.min'
            }),
            gulp.dest('core-bundle/contao/themes/flexible')
        ],
        cb
    );
});

gulp.task('minify-theme-icons', function (cb) {
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

gulp.task('minify-dark-theme-icons', function (cb) {
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

gulp.task('watch', function () {
    gulp.watch(
        [
            'core-bundle/public/core.js',
            'core-bundle/public/mootao.js'
        ],
        gulp.series('minify-public')
    );

    gulp.watch(
        [
            'core-bundle/contao/themes/flexible/*.js',
            '!core-bundle/contao/themes/flexible/*.min.js'
        ],
        gulp.series('minify-theme-js')
    );

    gulp.watch(
        [
            'core-bundle/contao/themes/flexible/*.css',
            '!core-bundle/contao/themes/flexible/*.min.css'
        ],
        gulp.series('minify-theme-css')
    );

    gulp.watch('core-bundle/contao/themes/flexible/icons/*.svg', gulp.series('minify-theme-icons'));
    gulp.watch('core-bundle/contao/themes/flexible/icons-dark/*.svg', gulp.series('minify-dark-theme-icons'));
});

gulp.task('default', gulp.parallel('minify-theme-js', 'minify-theme-css', 'minify-theme-icons', 'minify-dark-theme-icons'));
