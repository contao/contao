import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    static values = {
        parentMode: {
            type: Boolean,
            default: false,
        },
        requestToken: String,
        handle: String,
        draggable: String,
    };

    connect() {
        const options = {
            animation: 100,
            onSort: (event) => {
                this.#onSorted(event.item);
            },
        };

        if (this.hasHandleValue) {
            options.handle = this.handleValue;
        }

        if (this.hasDraggableValue) {
            options.draggable = this.draggableValue;
        }

        this.sortable = new Sortable(this.element, options);

        // Backwards compatibility for parent mode, will unhide the operation if no other drag handle is found
        for (const el of [...this.element.children]) {
            const handles = el.querySelectorAll('.drag-handle');

            // There will always be at least 2 handles: one for the operations list and one for the operations menu (which is hidden)
            if (handles.length === 2) {
                handles[0].style.display = '';
            }

            for (const handle of handles) {
                if (handle.style.display === 'none' && handle.parentNode.localName === 'li') {
                    handle.parentNode.style = 'display: none !important';
                }
            }
        }
    }

    disconnect() {
        this.sortable?.destroy();
        this.sortable = undefined;
    }

    move(event) {
        const item = this.#getItem(event.target);

        if (event.code === 'ArrowUp' || event.keyCode === 38) {
            event.preventDefault();

            if (item.previousElementSibling) {
                item.previousElementSibling.before(item);
            } else {
                this.element.append(item);
            }

            this.#onSorted(item);
            event.target.focus();
        } else if (event.code === 'ArrowDown' || event.keyCode === 40) {
            event.preventDefault();

            if (item.nextElementSibling) {
                item.nextElementSibling.after(item);
            } else {
                this.element.prepend(item);
            }

            this.#onSorted(item);
            event.target.focus();
        }
    }

    #updateWrapperLevel() {
        const divs = this.element.querySelectorAll('li > div:first-child');

        if (!divs) {
            return;
        }

        let wrapLevel = 0;

        for (let i = 0; i < divs.length; i++) {
            if (divs[i].classList.contains('wrapper_stop') && wrapLevel > 0) {
                wrapLevel--;
            }

            divs[i].className = divs[i].className.replace(/(^|\s)indent[^\s]*/g, '');

            if (wrapLevel > 0) {
                divs[i].classList.add('indent');
                divs[i].classList.add(`indent_${wrapLevel}`);
            }

            if (divs[i].classList.contains('wrapper_start')) {
                wrapLevel++;
            }

            divs[i].classList.remove('indent_first');
            divs[i].classList.remove('indent_last');

            if (divs[i - 1] && divs[i - 1].classList.contains('wrapper_start')) {
                divs[i].classList.add('indent_first');
            }

            if (divs[i + 1] && divs[i + 1].classList.contains('wrapper_stop')) {
                divs[i].classList.add('indent_last');
            }
        }
    }

    #updateParentSorting(el) {
        const url = new URL(window.location.href);

        url.searchParams.set('rt', this.requestTokenValue);
        url.searchParams.set('act', 'cut');
        url.searchParams.set('id', el.dataset.id);

        if (el.previousElementSibling) {
            url.searchParams.set('pid', el.previousElementSibling.dataset.id);
            url.searchParams.set('mode', 1);
        } else {
            url.searchParams.set('pid', this.element.dataset.id);
            url.searchParams.set('mode', 2);
        }

        fetch(url, {
            redirect: 'manual',
        });
    }

    #getItem(el) {
        if (!el.parentNode || el.parentNode === this.element) {
            return el;
        }

        return this.#getItem(el.parentNode);
    }

    #onSorted(item) {
        this.dispatch('update', { target: item });

        if (this.parentModeValue) {
            this.#updateWrapperLevel(item);
            this.#updateParentSorting(item);
        }
    }
}
