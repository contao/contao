'use strict';

const colorPreference = window.matchMedia('(prefers-color-scheme: dark)');
const colorSchemeToggle = document.getElementById('color-scheme-toggle');
const data = JSON.parse(document.getElementById('swagger-data').textContent);

const initColorMode = () => {
    let prefersDark = localStorage.getItem('contao--prefers-dark');

    if (prefersDark === null) {
        prefersDark = String(colorPreference.matches);
    }

    const dark = prefersDark === 'true';
    document.documentElement.dataset.colorScheme = dark ? 'dark' : 'light';
    colorSchemeToggle.setAttribute('aria-pressed', String(dark));
};

const renderDocumentation = () => {
    const probe = document.createElement('span');
    probe.hidden = true;
    document.body.append(probe);

    const color = name => {
        probe.style.color = `var(${name})`;
        return getComputedStyle(probe).color;
    };

    const fontFamily = getComputedStyle(document.body).fontFamily;

    Redoc.init(data.spec, {
        theme: {
            spacing: { sectionHorizontal: 20, sectionVertical: 32 },
            colors: {
                primary: { main: color('--blue') },
                text: { primary: color('--text'), secondary: color('--gray') },
                border: { dark: color('--content-border'), light: color('--content-border') },
                gray: { 50: color('--body-bg'), 100: color('--code-bg') },
                error: { main: color('--code-text') },
            },
            typography: {
                fontFamily,
                headings: { fontFamily, fontWeight: '600' },
                code: { color: color('--code-text'), backgroundColor: color('--code-bg') },
                links: { color: color('--blue'), visited: color('--blue'), hover: color('--blue') },
            },
            sidebar: {
                backgroundColor: color('--body-bg'),
                textColor: color('--text'),
                activeTextColor: color('--text'),
                groupItems: { activeBackgroundColor: color('--nav-current') },
                level1Items: { activeBackgroundColor: color('--nav-current') },
            },
            rightPanel: {
                backgroundColor: '#1b1d21',
                textColor: '#ddd',
                servers: {
                    overlay: { backgroundColor: color('--body-bg'), textColor: color('--text') },
                    url: { backgroundColor: color('--content-bg') },
                },
            },
            codeBlock: { backgroundColor: '#30343b' },
            schema: {
                typeNameColor: color('--gray'),
                typeTitleColor: color('--gray'),
                linesColor: color('--content-border'),
                nestedBackground: color('--body-bg'),
                requireLabelColor: color('--code-text'),
            },
            fab: { backgroundColor: color('--body-bg'), color: color('--text') },
            extensionsHook: name => ({
                PropertyDetailsCell: 'border-bottom-color: var(--content-border);',
                ConstraintItem: 'color: var(--constraint-text); background: var(--code-bg); border-color: var(--content-border);',
                ExampleValue: 'color: var(--text); background: var(--code-bg); border-color: var(--content-border);',
            })[name] || '',
        },
    }, document.getElementById('swagger-ui'));

    probe.remove();
};

const updateColorMode = () => {
    const previous = document.documentElement.dataset.colorScheme;
    initColorMode();

    if (previous !== document.documentElement.dataset.colorScheme) {
        renderDocumentation();
    }
};

colorSchemeToggle.hidden = false;
colorSchemeToggle.addEventListener('click', () => {
    localStorage.setItem('contao--prefers-dark', String(document.documentElement.dataset.colorScheme !== 'dark'));
    updateColorMode();
});

initColorMode();
renderDocumentation();
colorPreference.addEventListener('change', updateColorMode);
