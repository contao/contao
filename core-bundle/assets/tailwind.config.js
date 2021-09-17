const plugin = require('tailwindcss/plugin')

module.exports = {
    presets: [require('./tailwind-preset')],
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/aspect-ratio'),
        plugin(function ({addBase, theme}) {
            addBase({
                "[type='text'],[type='email'],[type='url'],[type='password'],[type='number'],[type='date'],[type='datetime-local'],[type='month'],[type='search'],[type='tel'],[type='time'],[type='week'],[multiple],textarea,select": {
                    width: '100%',
                    borderColor: theme('colors.gray.400'),
                    borderRadius: theme('borderRadius.sm'),
                    fontSize: theme('fontSize.sm'),
                    padding: '.125rem .333rem',
                    '&:focus': {
                        '--tw-ring-color': theme('colors.primary.300'),
                        '--tw-ring-offset-width': '1px',
                        '--tw-ring-shadow': `var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color)`,
                        'border-color': theme('colors.gray.400'),
                    },
                },
                "[type='checkbox'], [type='radio']": {
                    borderColor: theme('colors.gray.400'),
                    color: theme('colors.primary.DEFAULT'),
                    height: theme('spacing.3'),
                    width: theme('spacing.3'),
                },
                "[type='checkbox']": {
                    borderRadius: theme('borderRadius.sm'),
                },

            })
        })
    ],
};
