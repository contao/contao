import { Controller } from '@hotwired/stimulus';

Dropzone.autoDiscover = false;

export default class extends Controller {
    #dragged;
    #hoverTarget;
    #hoverTimer;
    #dropzone;

    static classes = ['dragging', 'dropping', 'ghost', 'uploading'];

    static targets = ['dropzone'];

    static values = {
        requestToken: String,
        root: String,
        canUpload: Boolean,
        uploadUrl: String,
        maxFilesize: Number,
        acceptedFiles: String,
    };

    connect() {
        if (this.canUploadValue && this.hasDropzoneTarget) {
            this.#createDropzone();
        }
    }

    disconnect() {
        this.#reset();
        this.#dropzone?.destroy();
        this.#dropzone = null;
    }

    onPointerDown(event) {
        const item = this.#getRow(event);

        if (!item) {
            return;
        }

        // Only allow row and empty space to start a drag and leave other content selectable
        item.draggable = event.target === item || event.target.classList.contains('tl_left');
    }

    onDragStart(event) {
        const item = this.#getRow(event);

        if (!item || (event.target !== item && !event.target.closest('.drag-handle'))) {
            return;
        }

        this.#dragged = item;
        this.element.classList.add(this.draggingClass);

        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', item.dataset.id);
        event.dataTransfer.setDragImage(...this.#createDragImage(item));
    }

    #createDragImage(item) {
        const row = item.querySelector(':scope > .tl_left') ?? item;
        const ghost = row.cloneNode(true);

        ghost.classList.add(this.ghostClass);
        ghost.style.left = '-10px';

        this.element.append(ghost);
        setTimeout(() => ghost.remove());

        return [ghost, 10, 10];
    }

    onDragOver(event) {
        if (!event.dataTransfer) {
            event.stopImmediatePropagation();
            return;
        }

        if (!this.#dragged) {
            // Allow propagating the event for Dropzone to register it
            return;
        }

        event.stopImmediatePropagation();

        const target = this.#findDroppableTarget(event.target);
        const pid = target && this.#getPid(target);

        if (!pid) {
            event.dataTransfer.dropEffect = 'none';
            this.#setDroppingClass(null);
            return;
        }

        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        this.#setDroppingClass(target);
    }

    onDragLeave(event) {
        // only clear class when leaving targets
        if (this.#dragged && !this.element.contains(event.relatedTarget)) {
            this.#setDroppingClass(null);
        }
    }

    onDrop(event) {
        const id = this.#dragged?.dataset.id;
        const pid = this.#hoverTarget && this.#getPid(this.#hoverTarget);

        if (!id || !pid) {
            return;
        }

        event.preventDefault();
        this.#reset();

        const url = new URL(window.location.href);

        url.searchParams.set('rt', this.requestTokenValue);
        url.searchParams.set('act', 'cut');
        url.searchParams.set('id', id);
        url.searchParams.set('pid', pid);
        url.searchParams.set('mode', 2);

        window.dispatchEvent(new Event('store-scroll-offset'));
        window.location.href = url.toString();
    }

    onDragEnd() {
        this.#reset();
    }

    #createDropzone() {
        this.#dropzone = new Dropzone(this.element, {
            url: this.uploadUrlValue,
            maxFilesize: this.maxFilesizeValue,
            acceptedFiles: this.acceptedFilesValue,
            paramName: 'files',
            params: {
                FORM_SUBMIT: 'tl_upload',
                action: 'fileupload',
            },
            previewsContainer: this.dropzoneTarget.querySelector('.dropzone-previews'),
            clickable: false,
        });

        this.#dropzone.on('queuecomplete', () => window.location.reload());

        this.#dropzone.on('dragover', (event) => {
            if (!this.#isFileDrag(event)) {
                return;
            }

            const target = this.#findDroppableTarget(event.target);
            const pid = target?.dataset.id;

            const url = new URL(this.uploadUrlValue, window.location.href);

            if (pid) {
                url.searchParams.set('pid', pid);
            }

            this.#dropzone.options.url = url.toString();
            this.#setDroppingClass(target);
        });

        this.#dropzone.on('drop', (event) => {
            if (this.#isFileDrag(event)) {
                this.dropzoneTarget.classList.add(this.uploadingClass);
                window.dispatchEvent(new Event('store-scroll-offset'));
            }
        });

        this.#dropzone.on('dragleave', () => {
            this.#dropzone.options.url = this.uploadUrlValue;
            this.#setDroppingClass(null);
        });
    }

    #isFileDrag(event) {
        return !!event.dataTransfer?.types?.includes('Files');
    }

    #findDroppableTarget(el) {
        const target = el?.closest?.('.tl_folder, .tl_folder_top, li.parent') ?? null;

        if (target?.classList.contains('parent')) {
            const folder = target.previousElementSibling;

            return folder?.classList.contains('tl_folder') ? folder : null;
        }

        return target;
    }

    #getPid(target) {
        const id = this.#dragged?.dataset.id;
        const pid = target.dataset.id ?? this.rootValue;

        if (!id || !pid || `${pid}/`.startsWith(`${id}/`) || `${pid}/` === id.replace(/[^/]+$/, '')) {
            return null;
        }

        return pid;
    }

    #setDroppingClass(target) {
        if (this.#hoverTarget === target) {
            return;
        }

        this.#hoverTarget?.classList.remove(this.droppingClass);
        clearTimeout(this.#hoverTimer);
        this.#hoverTarget = target;

        if (!target) {
            return;
        }

        target.classList.add(this.droppingClass);

        // Expand the folder after one second hover time
        const toggle = target.querySelector(':scope > .tl_left a.foldable:not(.foldable--open)');

        if (toggle) {
            this.#hoverTimer = setTimeout(() => toggle.click(), 1000);
        }
    }

    #getRow(event) {
        return event.target.closest('li[data-id]');
    }

    #reset() {
        this.#dragged = null;
        this.element.classList.remove(this.draggingClass);
        this.#setDroppingClass(null);
    }
}
