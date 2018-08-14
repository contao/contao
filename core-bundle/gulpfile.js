'use strict';

var gulp = require('gulp'),
    csso = require('gulp-csso'),
    ignore = require('gulp-ignore'),
    rename = require('gulp-rename'),
    svgo = require('gulp-svgo'),
    uglify = require('gulp-uglify'),
    pump = require('pump');

gulp.task('minify-public', function (cb) {
    pump([
            gulp.src('src/Resources/public/*.js'),
            ignore.exclude('*.min.js'),
            uglify(),
            rename({
                suffix: '.min'
            }),
            gulp.dest('src/Resources/public'),
        ],
        cb
    );
});

gulp.task('minify-theme-js', function (cb) {
    pump([
            gulp.src('src/Resources/contao/themes/flexible/src/*.js'),
            uglify(),
            gulp.dest('src/Resources/contao/themes/flexible'),
        ],
        cb
    );
});

gulp.task('minify-theme-css', function (cb) {
    pump([
            gulp.src('src/Resources/contao/themes/flexible/src/*.css'),
            csso({
                comments: false,
                restructure: false
            }),
            gulp.dest('src/Resources/contao/themes/flexible'),
        ],
        cb
    );
});

gulp.task('minify-theme-icons', function (cb) {
    pump([
            gulp.src('src/Resources/contao/themes/flexible/icons/*.svg'),
            svgo(),
            gulp.dest('src/Resources/contao/themes/flexible/icons'),
        ],
        cb
    );
});

gulp.task('watch', function () {
    gulp.watch(['src/Resources/public/*.js', '!src/Resources/public/*.min.js'], gulp.series('minify-public'));
    gulp.watch('src/Resources/contao/themes/flexible/src/*.js', gulp.series('minify-theme-js'));
    gulp.watch('src/Resources/contao/themes/flexible/src/*.css', gulp.series('minify-theme-css'));
    gulp.watch('src/Resources/contao/themes/flexible/icons/*.svg', gulp.series('minify-theme-icons'));
});

gulp.task('default', gulp.parallel('minify-public', 'minify-theme-js', 'minify-theme-css', 'minify-theme-icons'));
