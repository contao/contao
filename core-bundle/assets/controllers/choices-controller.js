import { Controller } from '@hotwired/stimulus';

export default class ChoicesController extends Controller {
    addMutationGuard = false;
    removeMutationGuard = false;

    connect() {
        if (this.addMutationGuard) {
            return;
        }

        // Choices wraps the element multiple times during initialization, leading to
        // multiple disconnects/reconnects of the controller that we need to ignore.
        this.addMutationGuard = true;

        const select = this.element;

        this.choices = new Choices(select, {
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
                const choices = select.closest('.choices')?.querySelector('.choices__list--dropdown > .choices__list');

                if (choices && select.dataset.placeholder) {
                    choices.dataset.placeholder = select.dataset.placeholder;
                }

                queueMicrotask(() => {
                    this.addMutationGuard = false;
                });
            },
            loadingText: Contao.lang.loading,
            noResultsText: Contao.lang.noResults,
            noChoicesText: Contao.lang.noOptions,
            removeItemLabelText: (value) => Contao.lang.removeItem.concat(' ').concat(value),
        });
    }

    disconnect() {
        if (this.addMutationGuard || this.removeMutationGuard) {
            return;
        }

        this._removeChoices();
    }

    beforeCache() {
        // Let choices unwrap the element container before Turbo caches the
        // page. It will be recreated, when the connect() call happens on the
        // restored page.
        this._removeChoices();
    }

    _removeChoices() {
        // Safely unwrap the element by preventing disconnect/connect calls
        // during the process.
        this.removeMutationGuard = true;

        this.choices?.destroy();
        this.choices = null;

        queueMicrotask(() => {
            this.removeMutationGuard = false;
        });
    }
}
