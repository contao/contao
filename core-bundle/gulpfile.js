'use strict';

var gulp = require('gulp'),
    csso = require('gulp-csso'),
    ignore = require('gulp-ignore'),
    rename = require('gulp-rename'),
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
            gulp.dest('src/Resources/public')
        ],
        cb
    );
});

gulp.task('minify-theme-js', function (cb) {
    pump([
            gulp.src('src/Resources/contao/themes/flexible/src/*.js'),
            uglify(),
            gulp.dest('src/Resources/contao/themes/flexible')
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
            gulp.dest('src/Resources/contao/themes/flexible')
        ],
        cb
    );
});

gulp.task('default', ['minify-public', 'minify-theme-js', 'minify-theme-css']);
