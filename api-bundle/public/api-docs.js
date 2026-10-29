'use strict';

const colorPreference = window.matchMedia('(prefers-color-scheme: dark)');
const colorSchemeToggle = document.getElementById('color-scheme-toggle');
const data = JSON.parse(document.getElementById('swagger-data').textContent);
const versionForm = document.getElementById('api-version-form');
const versionInput = document.getElementById('api-version');
const versionStatus = document.getElementById('api-version-status');
let loadingVersion = false;

const selectedVersion = () => data.spec.info.version;

const requestDocumentation = async (url, version) => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: { 'Contao-Api-Version': version },
    });

    if (!response.ok) {
        const problem = await response.json().catch(() => ({}));
        throw new Error(problem.detail || problem.description || problem.title || 'Unable to load the documentation.');
    }

    return response;
};

const loadVersion = async event => {
    event.preventDefault();

    if (loadingVersion || !versionForm.reportValidity()) {
        return;
    }

    loadingVersion = true;
    versionForm.setAttribute('aria-busy', 'true');
    versionForm.querySelector('button').disabled = true;
    versionStatus.textContent = 'Loading documentation…';

    try {
        const url = new URL(versionForm.dataset.specUrl, window.location.href);
        url.search = window.location.search;
        const response = await requestDocumentation(url, versionInput.value);
        const spec = await response.json();

        if (!spec.openapi || !spec.info?.version) {
            throw new Error('The server did not return an API specification.');
        }

        data.spec = spec;
        versionInput.value = selectedVersion();
        renderDocumentation();
        versionStatus.textContent = '';
    } catch (error) {
        versionStatus.textContent = `${error.message} Still showing API version ${selectedVersion()}.`;
    } finally {
        loadingVersion = false;
        versionForm.removeAttribute('aria-busy');
        versionForm.querySelector('button').disabled = false;
    }
};

const downloadDocumentation = async event => {
    event.preventDefault();
    const url = new URL(event.currentTarget.href);
    url.search = window.location.search;
    const version = selectedVersion();

    try {
        const response = await requestDocumentation(url, version);
        const blobUrl = URL.createObjectURL(await response.blob());
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = `contao-api-${version}.${/yaml/.test(url.pathname) ? 'yaml' : 'json'}`;
        document.body.append(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
    } catch (error) {
        versionStatus.textContent = `${error.message} The documentation could not be downloaded.`;
    }
};

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

versionForm.hidden = false;
versionInput.value = selectedVersion();
versionForm.addEventListener('submit', loadVersion);

for (const link of document.querySelectorAll('#formats a')) {
    if (/\.(json|jsonopenapi|yaml|yamlopenapi)$/.test(new URL(link.href).pathname)) {
        link.addEventListener('click', downloadDocumentation);
    }
}
