import { Controller } from '@hotwired/stimulus';

const prefersDark = () => {
    const prefersDark = localStorage.getItem('contao--prefers-dark');

    if (null === prefersDark) {
        return !!window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    return prefersDark === 'true';
}

export default class extends Controller {

    static targets = ['label'];
    static values = {
        i18n: {
            type: Object,
            default: { light: 'Disable dark mode', dark: 'Enable dark mode'}
        }
    };

    static afterLoad (_identifier, _application) {
        document.firstElementChild.dataset.colorScheme = prefersDark() ? 'dark' : 'light';
    }

    initialize () {
        this.toggle = this.toggle.bind(this);
    }

    connect () {
        this.element.addEventListener('click', this.toggle);

        if (this.hasLabelTarget) {
            this.labelTarget.innerText = this.i18nValue[prefersDark() ? 'light' : 'dark'];
        }
    }

    disconnect () {
        this.element.removeEventListener('click', this.toggle);
    }

    toggle (e) {
        e.preventDefault();

        const isDark = !prefersDark();

        if (isDark === window.matchMedia('(prefers-color-scheme: dark)').matches) {
            localStorage.removeItem('contao--prefers-dark');
        } else {
            localStorage.setItem('contao--prefers-dark', String(isDark));
        }

        document.firstElementChild.dataset.colorScheme = isDark ? 'dark' : 'light';

        // Change the label after the dropdown is hidden
        setTimeout(() => {
            this.labelTarget.innerText = this.i18nValue[isDark ? 'light' : 'dark'];
        }, 300);
    }
}
