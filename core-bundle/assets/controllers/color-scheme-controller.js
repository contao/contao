import { Controller } from '@hotwired/stimulus';

const prefersDark = () => {
    const prefersDark = localStorage.getItem('contao--prefers-dark');

    if (null === prefersDark) {
        return !!window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    return prefersDark === 'true';
}

const setColorScheme = () => {
    document.documentElement.dataset.colorScheme = prefersDark() ? 'dark' : 'light';
};

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', setColorScheme);
setColorScheme();

export default class extends Controller {
    static targets = ['label'];

    static values = {
        i18n: {
            type: Object,
            default: { light: 'Disable dark mode', dark: 'Enable dark mode' }
        }
    };

    initialize () {
        this.toggle = this.toggle.bind(this);
        this.setLabel = this.setLabel.bind(this);
    }

    connect () {
        this.element.addEventListener('click', this.toggle);

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', this.setLabel);
        this.setLabel();
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

        setColorScheme();

        // Change the label after the dropdown is hidden
        setTimeout(this.setLabel, 300);
    }

    setLabel () {
        if (this.hasLabelTarget) {
            this.labelTarget.innerText = this.i18nValue[prefersDark() ? 'light' : 'dark'];
        }
    }
}
