const colors = require('tailwindcss/colors')

const projectDir = process.cwd().replace(/\/assets$/, '').replace(/\/src\/Resources$/, '')

module.exports = {
    darkMode: 'class',
    purge: [
        projectDir + '/src/Resources/views/**/*.html.twig',
        projectDir + '/vendor/**/Resources/views/**/*.html.twig',
    ],
    theme: {
        colors: {
            transparent: 'transparent',
            current: 'currentColor',
            black: colors.black,
            white: colors.white,
            gray: colors.coolGray,
            red: colors.rose,
            primary: {
                50: 'var(--color-primary-50, #FFF8F1)',
                100: 'var(--color-primary-100, #FFEBD5)',
                200: 'var(--color-primary-200, #FFCF9D)',
                300: 'var(--color-primary-300, #FFB365)',
                400: 'var(--color-primary-400, #FF982D)',
                500: 'var(--color-primary-500, #F47C00)',
                600: 'var(--color-primary-600, #DB6F00)',
                700: 'var(--color-primary-700, #C16200)',
                800: 'var(--color-primary-800, #A85500)',
                900: 'var(--color-primary-900, #8E4800)',
            },
        }
    },
    plugins: [],
}
