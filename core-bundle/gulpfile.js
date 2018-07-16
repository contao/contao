'use strict';

var gulp = require('gulp'),
    csso = require('gulp-csso'),
    ignore = require('gulp-ignore'),
    livereload = require('gulp-livereload'),
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
            livereload()
        ],
        cb
    );
});

gulp.task('minify-theme-js', function (cb) {
    pump([
            gulp.src('src/Resources/contao/themes/flexible/src/*.js'),
            uglify(),
            gulp.dest('src/Resources/contao/themes/flexible'),
            livereload()
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
            livereload()
        ],
        cb
    );
});

gulp.task('minify-theme-icons', function (cb) {
    pump([
            gulp.src('src/Resources/contao/themes/flexible/icons/*.svg'),
            svgo(),
            gulp.dest('src/Resources/contao/themes/flexible/icons'),
            livereload()
        ],
        cb
    );
});

gulp.task('watch', function () {
    livereload.listen();
    gulp.watch(['src/Resources/public/*.js', '!src/Resources/public/*.min.js'], ['minify-public']);
    gulp.watch('src/Resources/contao/themes/flexible/src/*.js', ['minify-theme-js']);
    gulp.watch('src/Resources/contao/themes/flexible/src/*.css', ['minify-theme-css']);
    gulp.watch('src/Resources/contao/themes/flexible/icons/*.svg', ['minify-theme-icons']);
});

gulp.task('default', ['minify-public', 'minify-theme-js', 'minify-theme-css', 'minify-theme-icons']);
