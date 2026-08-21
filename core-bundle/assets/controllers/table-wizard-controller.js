import { computePosition, flip, offset, shift } from '@floating-ui/dom';
import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

function positionAt(element, anchor, placement) {
    computePosition(anchor, element, {
        placement,
        middleware: [offset(6), flip(), shift({ padding: 10 })],
    }).then(({ x, y }) => Object.assign(element.style, { left: `${x}px`, top: `${y}px` }));
}

export default class extends Controller {
    #menu = null;
    #dragging = false;
    #syncing = false;
    #observer;
    #flags = { head: false, foot: false, left: false };

    static targets = ['body', 'row', 'columnHandle', 'columnHandleRow', 'input', 'menuTemplate'];

    static values = {
        name: String,
        zoom: { type: Number, default: 1.1 },
        storageKey: { type: String, default: 'contao_table_wizard_cell_size' },
        appearance: Object,
    };

    connect() {
        this.rowSortable = new Sortable(this.bodyTarget, {
            animation: 100,
            handle: '.table-wizard-handle-row',
            draggable: '.table-wizard-row',
            forceFallback: false,
            delayOnTouchOnly: true,
            delay: 150,
            touchStartThreshold: 5,
            onSort: () => this.#updateNames(),
        });

        this.columnSortable = new Sortable(this.columnHandleRowTarget, {
            animation: 100,
            direction: 'horizontal',
            handle: '.table-wizard-handle-column',
            draggable: '.table-wizard-handle-cell',
            chosenClass: 'table-wizard-handle-cell-dragging',
            fallbackClass: 'table-wizard-handle-cell-fallback',
            forceFallback: false,
            delayOnTouchOnly: true,
            delay: 150,
            touchStartThreshold: 5,
            onStart: () => this.#updateSortIndex(),
            onChange: () => this.#applyColumnOrder(),
            onEnd: () => {
                this.#applyColumnOrder();
                this.columnHandleTargets.forEach((cell) => {
                    delete cell.dataset.sortIndex;
                });
                this.#updateNames();
            },
        });

        this.#observer = new ResizeObserver((entries) => this.#syncCellSizes(entries));

        this.inputTargets.forEach((textarea) => {
            this.#observer.observe(textarea);
        });

        this.#applyStoredSize();
        this.syncAppearance();
    }

    disconnect() {
        this.closeMenu(false);
        this.rowSortable?.destroy();
        this.columnSortable?.destroy();
        this.#observer?.disconnect();
    }

    inputTargetConnected(textarea) {
        this.#observer?.observe(textarea);
    }

    inputTargetDisconnected(textarea) {
        this.#observer?.unobserve(textarea);
    }

    openMenu({ currentTarget }) {
        this.#showMenu(currentTarget, this.#getAxis(currentTarget));
    }

    closeMenu(restoreFocus = true) {
        if (null === this.#menu) {
            return;
        }

        const { element, handle } = this.#menu;
        this.#menu = null;

        element.remove();

        handle.setAttribute('aria-expanded', 'false');
        this.#clearActive();

        if (restoreFocus) {
            handle.focus();
        }
    }

    handleMenu(event) {
        const handle = event.currentTarget;
        const axis = this.#getAxis(handle);

        if ('Enter' === event.key || ' ' === event.key) {
            event.preventDefault();
            this.#showMenu(handle, axis);
            return;
        }

        const back = 'row' === axis ? 'ArrowUp' : 'ArrowLeft';
        const forth = 'row' === axis ? 'ArrowDown' : 'ArrowRight';

        if (event.key !== back && event.key !== forth) {
            return;
        }

        event.preventDefault();

        const index = this.#index(handle, axis);
        const to = event.key === back ? index - 1 : index + 1;

        if (to >= 0 && to < this.#count(axis)) {
            this.#move(axis, index, to);
            this.#handle(axis, to).focus();
        }
    }

    syncAppearance() {
        for (const name of Object.keys(this.#flags)) {
            this.element.classList.toggle(`table-wizard-has-${name}`, this.#isEnabled(name));
        }
    }

    highlight({ currentTarget }) {
        this.#setActive(this.#getAxis(currentTarget), this.#index(currentTarget, this.#getAxis(currentTarget)));
    }

    clearHighlight() {
        if (null === this.#menu) {
            this.#clearActive();
        }
    }

    addCells({ currentTarget }) {
        if (this.#dragging) {
            return;
        }

        const axes = this.#addAxes(currentTarget);

        for (const axis of axes) {
            this.#insert(axis, this.#count(axis));
        }

        this.#focusCell(
            axes.includes('row') ? this.#count('row') - 1 : 0,
            axes.includes('column') ? this.#count('column') - 1 : 0,
        );
    }

    startDrag(event) {
        if (0 !== event.button) {
            return;
        }

        this.#dragging = false;

        const axes = this.#addAxes(event.currentTarget);
        const rect = this.#getCells(this.rowTargets[0])[0].getBoundingClientRect();
        const step = { row: rect.height || 40, column: rect.width || 40 };
        const origin = { row: event.clientY, column: event.clientX };
        const initial = { row: this.#count('row'), column: this.#count('column') };

        const onPointerMove = (event) => {
            const position = { row: event.clientY, column: event.clientX };

            for (const axis of axes) {
                const delta = position[axis] - origin[axis];
                this.#dragging = this.#dragging || Math.abs(delta) > 3;
                const wanted = Math.max(1, initial[axis] + Math.round(delta / step[axis]));
                const empty = () => this.#values(axis, this.#count(axis) - 1).every((value) => '' === value.trim());

                while (this.#count(axis) < wanted) {
                    this.#insert(axis, this.#count(axis));
                }

                while (this.#count(axis) > wanted && this.#count(axis) > 1 && empty()) {
                    this.#remove(axis, this.#count(axis) - 1);
                }
            }
        };

        const onPointerUp = () => {
            window.removeEventListener('pointermove', onPointerMove);
            window.removeEventListener('pointerup', onPointerUp);
            // Use rAF so addCell doesn't trigger
            requestAnimationFrame(() => {
                this.#dragging = false;
            });
        };

        window.addEventListener('pointermove', onPointerMove);
        window.addEventListener('pointerup', onPointerUp);
    }

    // Keyboard navigation
    navigate(event) {
        const textarea = event.target;
        const key = event.key;

        if (textarea.selectionStart !== textarea.selectionEnd) {
            return;
        }

        let moved = false;

        const cell = textarea.closest('.table-wizard-cell');
        const row = cell.closest('.table-wizard-row');

        const i = this.rowTargets.indexOf(row);
        const j = this.#getCells(row).indexOf(cell);

        const cursorAtStart = 0 === textarea.selectionStart;
        const cursorAtEnd = textarea.value.length === textarea.selectionStart;

        if ('ArrowLeft' === key && cursorAtStart) {
            moved = this.#focusCell(i, j - 1, 'end');
        } else if ('ArrowRight' === key && cursorAtEnd) {
            moved = this.#focusCell(i, j + 1);
        } else if ('ArrowUp' === key && cursorAtStart) {
            moved = this.#focusCell(i - 1, j, 'end');
        } else if ('ArrowDown' === key && cursorAtEnd) {
            moved = this.#focusCell(i + 1, j);
        }

        if (moved) {
            event.preventDefault();
        }
    }

    expandCellSizes() {
        this.#resize(this.zoomValue);
    }

    shrinkCellSizes() {
        this.#resize(1 / this.zoomValue);
    }

    documentClick(event) {
        if (!this.#menu || this.#menu.element.contains(event.target) || this.#menu.handle.contains(event.target)) {
            return;
        }

        this.closeMenu(false);
    }

    #showMenu(handle, axis) {
        const open = this.#menu?.handle === handle;
        this.closeMenu();

        if (open) {
            return;
        }

        const index = this.#index(handle, axis);
        const count = this.#count(axis);
        const menu = this.menuTemplateTarget.content.firstElementChild.cloneNode(true);

        for (const item of menu.querySelectorAll('[data-axis]')) {
            const positions = (item.dataset.position || '').split(' ').filter(Boolean);
            const operation = item.dataset.operation;

            if (
                !item.dataset.axis.split(' ').includes(axis) ||
                (positions.length && !this.#matches(positions, index, count))
            ) {
                item.remove();
                continue;
            }

            if (!operation) {
                continue;
            }

            if (operation.startsWith('toggle')) {
                item.setAttribute('aria-checked', this.#isEnabled(this.#flag(operation)) ? 'true' : 'false');
            }

            item.addEventListener('click', (event) => {
                event.preventDefault();
                this.#run(operation, axis, index);
            });
        }

        for (const separator of menu.querySelectorAll('.table-wizard-menu-separator')) {
            if (!separator.previousElementSibling || !separator.nextElementSibling) {
                separator.remove();
            }
        }

        menu.addEventListener('keydown', (event) => this.#navigateMenu(event));
        this.element.appendChild(menu);
        positionAt(menu, handle, 'row' === axis ? 'right-start' : 'bottom-start');
        handle.setAttribute('aria-expanded', 'true');
        this.#setActive(axis, index);
        menu.querySelector('.table-wizard-menu-item')?.focus();
        this.#menu = { element: menu, handle, axis, index };
    }

    #navigateMenu(event) {
        const items = Array.from(this.#menu.element.querySelectorAll('.table-wizard-menu-item'));
        const current = items.indexOf(document.activeElement);

        if ('ArrowDown' === event.key) {
            event.preventDefault();
            items[(current + 1) % items.length].focus();
        } else if ('ArrowUp' === event.key) {
            event.preventDefault();
            items[(current - 1 + items.length) % items.length].focus();
        } else if ('Tab' === event.key) {
            event.preventDefault();
        }
    }

    #matches(positions, index, count) {
        return positions.some(
            (position) =>
                ('first' === position && 0 === index) || ('last' === position && count > 1 && index === count - 1),
        );
    }

    #run(operation, axis, index) {
        if (operation.startsWith('toggle')) {
            this.#toggle(this.#flag(operation), operation);
            return;
        }

        this.closeMenu();

        switch (operation) {
            case 'insertBefore':
                this.#insert(axis, index);
                break;

            case 'insertAfter':
                this.#insert(axis, index + 1);
                break;

            case 'duplicate':
                this.#insert(axis, index + 1, this.#values(axis, index));
                break;

            case 'clear':
                this.#setValues(this.#inputCells(axis, index), null);
                break;

            case 'remove':
                this.#remove(axis, index);
                break;
        }
    }

    #setActive(axis, index) {
        this.#clearActive();

        for (const cell of this.#inputCells(axis, index)) {
            cell?.classList.add('active');
        }
    }

    #clearActive() {
        for (const cell of this.element.querySelectorAll('.table-wizard-cell.active')) {
            cell.classList.remove('active');
        }
    }

    #getAxis(handle) {
        return handle.classList.contains('table-wizard-handle-row') ? 'row' : 'column';
    }

    #getCells(row) {
        if (!row) {
            return [];
        }

        return Array.from(row.querySelectorAll(':scope > .table-wizard-cell'));
    }

    #count(axis) {
        return 'row' === axis ? this.rowTargets.length : this.columnHandleTargets.length;
    }

    #index(handle, axis) {
        const cell = handle.closest('.table-wizard-handle-cell');

        return 'row' === axis
            ? this.rowTargets.indexOf(cell.closest('.table-wizard-row'))
            : this.columnHandleTargets.indexOf(cell);
    }

    #handle(axis, index) {
        const scope = 'row' === axis ? this.rowTargets[index] : this.columnHandleTargets[index];

        return scope.querySelector(`.table-wizard-handle-${axis}`);
    }

    #inputCells(axis, index) {
        return 'row' === axis
            ? this.#getCells(this.rowTargets[index])
            : this.rowTargets.map((row) => this.#getCells(row)[index]);
    }

    #values(axis, index) {
        return this.#inputCells(axis, index).map((cell) => cell?.querySelector('textarea')?.value ?? '');
    }

    #setValues(cells, values) {
        cells.forEach((cell, i) => {
            const textarea = cell?.querySelector('textarea');

            if (textarea) {
                textarea.value = values ? (values[i] ?? '') : '';
            }
        });
    }

    #clone(el) {
        const clone = el.cloneNode(true);
        clone.classList.remove('active');

        for (const cell of clone.querySelectorAll('.table-wizard-cell.active')) {
            cell.classList.remove('active');
        }

        for (const handle of clone.querySelectorAll('.table-wizard-handle')) {
            handle.setAttribute('aria-expanded', 'false');
        }

        return clone;
    }

    #insert(axis, index, values = null) {
        const source = Math.min(index, this.#count(axis) - 1);

        if ('row' === axis) {
            const row = this.#clone(this.rowTargets[source]);
            this.#setValues(this.#getCells(row), values);
            this.#place(row, this.rowTargets[index], this.bodyTarget);
        } else {
            const handle = this.#clone(this.columnHandleTargets[source]);
            this.#place(handle, this.columnHandleTargets[index], this.columnHandleRowTarget);

            this.rowTargets.forEach((row, i) => {
                const cells = this.#getCells(row);
                const cell = this.#clone(cells[source]);
                this.#setValues([cell], values ? [values[i]] : null);
                this.#place(cell, cells[index], row);
            });
        }

        this.#updateNames();
    }

    #place(el, before, parent) {
        before ? before.before(el) : parent.appendChild(el);
    }

    #remove(axis, index) {
        if (this.#count(axis) < 2) {
            this.#setValues(this.#inputCells(axis, index), null);
            return;
        }

        if ('row' === axis) {
            this.rowTargets[index].remove();
        } else {
            this.columnHandleTargets[index].remove();
            this.rowTargets.forEach((row) => {
                this.#getCells(row)[index].remove();
            });
        }

        this.#updateNames();
    }

    #move(axis, from, to) {
        const shift = (elements) =>
            to > from ? elements[to].after(elements[from]) : elements[to].before(elements[from]);

        if ('row' === axis) {
            shift(this.rowTargets);
        } else {
            shift(this.columnHandleTargets);
            this.rowTargets.forEach((row) => {
                shift(this.#getCells(row));
            });
        }

        this.#updateNames();
    }

    #updateSortIndex() {
        this.columnHandleTargets.forEach((cell, i) => {
            cell.dataset.sortIndex = i;
        });
    }

    /**
     * Syncs the order from the handle cell order that was already moved by SortableJS
     * Maybe they could be reordered instead of rebuilt but w/e.
     */
    #applyColumnOrder() {
        const handles = this.columnHandleTargets.filter(
            (cell) =>
                undefined !== cell.dataset.sortIndex && !cell.classList.contains('table-wizard-handle-cell-fallback'),
        );

        const order = handles.map((cell) => Number(cell.dataset.sortIndex));

        // Bail on anything unexpected (e.g. on touch)
        if (order.some((from) => !Number.isInteger(from) || from < 0)) {
            return;
        }

        if (order.every((from, to) => from === to)) {
            return;
        }

        for (const row of this.rowTargets) {
            const cells = this.#getCells(row);

            if (cells.length !== order.length) {
                continue;
            }

            const fragment = document.createDocumentFragment();

            for (const from of order) {
                const cell = cells[from];

                if (cell) {
                    fragment.appendChild(cell);
                }
            }

            row.appendChild(fragment);
        }

        this.#updateSortIndex();
    }

    #updateNames() {
        this.rowTargets.forEach((row, i) => {
            this.#getCells(row).forEach((cell, j) => {
                const textarea = cell?.querySelector('textarea');

                if (textarea) {
                    textarea.name = `${this.nameValue}[${i}][${j}]`;
                }
            });
        });
    }

    #addAxes(button) {
        if (button.classList.contains('table-wizard-add-row')) {
            return ['row'];
        }

        return button.classList.contains('table-wizard-add-column') ? ['column'] : ['row', 'column'];
    }

    #focusCell(i, j, caret = 'start') {
        const textarea = this.rowTargets[i] && this.#getCells(this.rowTargets[i])[j]?.querySelector('textarea');

        if (!textarea) {
            return false;
        }

        const position = 'end' === caret ? textarea.value.length : 0;

        textarea.focus();
        textarea.setSelectionRange(position, position);

        return true;
    }

    #resize(factor) {
        let size = '';

        for (const textarea of document.querySelectorAll('.table-wizard-input')) {
            const rect = textarea.getBoundingClientRect();
            textarea.style.width = `${Math.round(rect.width * factor)}px`;
            textarea.style.height = `${Math.round(rect.height * factor)}px`;

            const clamped = textarea.getBoundingClientRect();
            textarea.style.width = `${Math.round(clamped.width)}px`;
            textarea.style.height = `${Math.round(clamped.height)}px`;
            size ||= `${textarea.style.width}|${textarea.style.height}`;
        }

        window.localStorage.setItem(this.storageKeyValue, size);
    }

    #syncCellSizes(entries) {
        if (this.#syncing) {
            return;
        }

        this.#syncing = true;

        for (const { target } of entries) {
            const cell = target.closest('.table-wizard-cell');
            const row = cell.closest('.table-wizard-row');

            const width = target.style.width || `${Math.round(target.getBoundingClientRect().width)}px`;
            const height = target.style.height || `${Math.round(target.getBoundingClientRect().height)}px`;

            this.#eachInput('column', this.#getCells(row).indexOf(cell), (input) => (input.style.width = width));
            this.#eachInput('row', this.rowTargets.indexOf(row), (input) => (input.style.height = height));
        }

        queueMicrotask(() => (this.#syncing = false));
    }

    #eachInput(axis, index, callback) {
        for (const cell of this.#inputCells(axis, index)) {
            const input = cell?.querySelector('textarea');

            if (input) {
                callback(input);
            }
        }
    }

    #applyStoredSize() {
        const size = window.localStorage.getItem(this.storageKeyValue);

        if (null === size) {
            return;
        }

        const [width, height] = size.split('|');

        this.inputTargets.forEach((textarea) => {
            Object.assign(textarea.style, { width, height });
        });
    }

    #flag(operation) {
        return { toggleHead: 'head', toggleFoot: 'foot', toggleLeft: 'left' }[operation];
    }

    #getAppearanceCheckbox(name) {
        const field = this.appearanceValue[name];

        return field ? this.element.closest('form')?.querySelector(`input[type="checkbox"][name="${field}"]`) : null;
    }

    #isEnabled(name) {
        return this.#getAppearanceCheckbox(name)?.checked ?? this.#flags[name];
    }

    #toggle(name, operation) {
        const enabled = !this.#isEnabled(name);
        const checkbox = this.#getAppearanceCheckbox(name);
        this.#flags[name] = enabled;

        if (checkbox) {
            checkbox.checked = enabled;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }

        this.syncAppearance();

        this.#menu?.element
            .querySelector(`[data-operation="${operation}"]`)
            ?.setAttribute('aria-checked', enabled ? 'true' : 'false');
    }
}
