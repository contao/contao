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
        this.sortable = new Sortable(this.element, {
            animation: 100,
            handle: this.handleValue,
            draggable: this.draggableValue,
            onSort: (event) => {
                this.dispatch('update', { target: event.item });

                if (this.parentModeValue) {
                    this._updateWrapperLevel(event.item);
                    this._updateParentSorting(event.item);
                }
            },
        });

        // Backwards compatibility for parent mode, will unhide the operation if no other drag handle is found
        for (const el of [...this.element.children]) {
            const handles = el.querySelectorAll('.drag-handle');

            if (handles.length === 1) {
                handles[0].style.display = '';
            }
        }
    }

    disconnect() {
        this.sortable?.destroy();
        this.sortable = undefined;
    }

    _updateWrapperLevel(el) {
        const ul = el.closest('ul');

        if (!ul) {
            return;
        }

        const divs = ul.querySelectorAll('li > div:first-child');

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

    _updateParentSorting(el) {
        const url = new URL(window.location.href);

        url.searchParams.set('rt', this.requestTokenValue);
        url.searchParams.set('act', 'cut');
        url.searchParams.set('id', el.dataset.id);

        if (el.previousElementSibling) {
            url.searchParams.set('pid', el.previousElementSibling.dataset.id);
            url.searchParams.set('mode', 1);
        } else {
            url.searchParams.set('pid', el.closest('ul').dataset.id);
            url.searchParams.set('mode', 2);
        }

        fetch(url, {
            redirect: 'manual',
        });
    }
}
