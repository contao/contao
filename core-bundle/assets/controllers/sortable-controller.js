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
        group: String,
    };

    static targets = ['primaryHandle', 'fallbackHandle'];

    connect() {
        // Avoid duplicate if instance still exists (see disconnect())
        if (this.sortable) {
            return;
        }

        const options = {
            animation: 100,
            swapThreshold: 0.65,
            onSort: (event) => {
                this.#onSorted(event.item, event);
            },
            onMove: (event) => this.#onMove(event),
            onEnd: () => this.#highlight(),
        };

        if (this.hasHandleValue) {
            options.handle = this.handleValue;
        }

        if (this.hasDraggableValue) {
            options.draggable = this.draggableValue;
        }

        if (this.hasGroupValue) {
            options.group = this.groupValue;
        }

        this.sortable = new Sortable(this.element, options);
    }

    /**
     * @deprecated Deprecated since Contao 5.7, to be removed in Contao 7.
     */
    fallbackHandleTargetConnected(el) {
        // No need to remove the class if a primaryHandleTarget exists (see #8859)
        if (this.hasPrimaryHandleTarget) {
            return;
        }

        // Backwards compatibility for parent mode, will unhide the operation that is not inside the operations menu
        if (!el.closest('.operations-menu')) {
            el.classList.remove('hidden');
        }
    }

    disconnect() {
        // Don't disconnect sortables target whilst it is still connected to the dom when dragging it
        if (this.element.isConnected) {
            return;
        }

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
        this.#updateLevel(el);

        // Do not treat top nodes as siblings (e.g. page tree top node)
        const previous = el.previousElementSibling?.dataset.id ? el.previousElementSibling : null;

        const url = new URL(window.location.href);

        url.searchParams.set('rt', this.requestTokenValue);
        url.searchParams.set('act', 'cut');
        url.searchParams.set('id', el.dataset.id);

        if (previous) {
            url.searchParams.set('pid', previous.dataset.id);
            url.searchParams.set('mode', 1);
        } else {
            // Record dropped into another list
            url.searchParams.set('pid', el.parentNode.dataset.id);
            url.searchParams.set('mode', 2);
        }

        fetch(url, {
            redirect: 'manual',
        });
    }

    #getLevel(el) {
        let level = 0;

        for (let list = el.parentNode; list; list = list.parentElement?.closest('ul[data-id]')) {
            level++;
        }

        return level;
    }

    #previewLevel(el) {
        const label = el.querySelector(':scope > .tl_folder > .tl_left, :scope > .tl_file > .tl_left');

        label?.style.setProperty('--level', String(this.#getLevel(el) - (label.querySelector('a.foldable') ? 1 : 0)));
    }

    #updateLevel(el) {
        this.#previewLevel(el);

        for (const child of el.querySelectorAll('li[data-id]')) {
            this.#previewLevel(child);
        }
    }

    #onMove(event) {
        // Prevent dragging into the subtree
        if (event.dragged.contains(event.to)) {
            return false;
        }

        const draggingRootPage = this.#isRootPage(event.dragged);

        // Do not sort root items into subtrees
        if (draggingRootPage && event.to.dataset.id !== '0') {
            return false;
        }

        // Do not sort normal items into the root (only root items allowed)
        if (!draggingRootPage && event.to.dataset.id === '0') {
            return false;
        }

        const targetOwner = event.to.closest('li[data-id]');

        // Do not allow leaf records (MODE_TREE_EXTENDED) to be sorted into root pages
        if (event.dragged.hasAttribute('data-leaf-record') && targetOwner?.hasAttribute('data-root-page')) {
            return false;
        }

        this.#previewLevel(event.dragged);

        this.#highlight(targetOwner);

        return true;
    }

    #highlight(el = null) {
        document.querySelector('.tl_folder_dropping')?.classList.remove('tl_folder_dropping');
        el?.querySelector(':scope > .tl_folder, :scope > .tl_file')?.classList.add('tl_folder_dropping');
    }

    #getItem(el) {
        if (!el.parentNode || el.parentNode === this.element) {
            return el;
        }

        return this.#getItem(el.parentNode);
    }

    #onSorted(item, event) {
        // Only dispatch sorting update on the target
        if (event && event.to !== this.element) {
            return;
        }

        this.dispatch('update', { target: item });

        if (this.parentModeValue) {
            this.#updateWrapperLevel(item);
            this.#updateParentSorting(item);
        }
    }

    #isRootPage(el) {
        return el.hasAttribute('data-root-page');
    }
}
