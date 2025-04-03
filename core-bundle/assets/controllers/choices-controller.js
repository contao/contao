import { Controller } from '@hotwired/stimulus';
import Choices from 'choices.js';
import css from '!!css-loader!../styles/component/choices.pcss';

export default class ChoicesController extends Controller {
    static values = { config: Object };

    static styleSheet = null;

    initialize() {
        if (ChoicesController.styleSheet) {
            return;
        }

        ChoicesController.styleSheet = new CSSStyleSheet();
        ChoicesController.styleSheet.replace(css);
    }

    connect() {
        // Choices wraps/unwraps the underlying select element when the instance is created/destroyed and may create a
        // lot of DOM nodes. To prevent interference with our Stimulus mutation observers, we therefore isolate it in a
        // shadow root with its own cloned select element.
        const [shadowRoot, select] = this._initializeShadowRoot();

        const config = {
            shadowRoot: shadowRoot,
            shouldSort: false,
            duplicateItemsAllowed: false,
            allowHTML: false,
            removeItemButton: true,
            searchEnabled: select.options.length > 7,
            classNames: {
                containerOuter: ['choices', ...Array.from(select.classList)],
                flippedState: '',
            },
            fuseOptions: {
                includeScore: true,
                threshold: 0.4,
            },
            callbackOnInit: () => {
                const choices = shadowRoot.querySelector('.choices__list--dropdown > .choices__list');

                if (choices && select.dataset.placeholder) {
                    choices.dataset.placeholder = select.dataset.placeholder;
                }
            },
            itemSelectText: '', // suppress the "Press to select" text from taking half of the width
            loadingText: Contao.lang.loading,
            noResultsText: Contao.lang.noResults,
            noChoicesText: Contao.lang.noOptions,
            removeItemLabelText: function (value) {
                return Contao.lang.removeItem.concat(' ').concat(value);
            },
        };

        // Allow others to alter the config before we create the instance
        this.dispatch('create', {
            detail: { select, config: Object.assign(config, this.configValue) },
        });

        this.choices = new Choices(select, config);
    }

    disconnect() {
        this._restoreInitialState();
    }

    beforeCache() {
        // Restore changes to the DOM. They will get recreated, when the connect() call happens on the restored page.
        this._restoreInitialState();
    }

    _initializeShadowRoot() {
        // Create a sibling host element and hide the initial element
        this._host = document.createElement('div');
        this._host.setAttribute('data-contao--color-scheme-target', 'outlet');

        this.element.insertAdjacentElement('afterend', this._host);
        this.element.classList.add('hidden');

        // Clone the initial element and link it with a "change" event listener
        const selectForChoices = this.element.cloneNode(true);

        selectForChoices.addEventListener('change', () => {
            this.element.value = selectForChoices.value;
        });

        // Create a shadow root where the cloned element and the Choices instance will live
        const shadowRoot = this._host.attachShadow({ mode: 'open' });

        shadowRoot.appendChild(selectForChoices);
        shadowRoot.adoptedStyleSheets.push(ChoicesController.styleSheet);

        return [shadowRoot, selectForChoices];
    }

    _restoreInitialState() {
        // Make sure any document-wide event listeners are removed, so we aren't leaking memory
        this.choices.destroy();

        // Remove the host element and restore initial styling
        this._host.remove();
        this.element.classList.remove('hidden');
    }
}
