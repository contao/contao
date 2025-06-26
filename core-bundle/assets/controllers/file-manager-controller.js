import { Dropzone } from '@deltablot/dropzone';
import { Controller } from '@hotwired/stimulus';
import { TurboStreamConnection } from '../modules/turbo-stream-connection';

export default class FileManagerController extends Controller {
    static values = {
        listElementsOperationsUrl: String,
        uploadOperationUrl: String,
        moveOperationUrl: String,
    };

    static targets = ['elementsOperations', 'listing', 'dropzone'];
    static classes = ['listLayout', 'gridLayout', 'dragging', 'dropping'];

    viewStreamConnection = new TurboStreamConnection();
    actionStreamConnection = new TurboStreamConnection();
    dropzone = null;

    _currentUploadUrl = '';
    _draggedPaths = [];

    connect() {
        this.dropzone = new Dropzone(this.dropzoneTarget, {
            url: () => this._currentUploadUrl,
            createImageThumbnails: false,
            clickable: false,
        });

        this.dropzone.on('sending', (file, xhr) => {
            xhr.setRequestHeader('Accept', 'text/vnd.turbo-stream.html');
        });

        this.dropzone.on('complete', (file) => {
            setTimeout(() => this.dropzone.removeFile(file), 2500);
        });

        this.dropzone.on('success', (_, htmlResponse) => {
            Turbo.renderStreamMessage(htmlResponse);
        });
    }

    disconnect() {
        this.dropzone.destroy();
    }

    beforeCache() {
        // Destroy dropzone instance, so that no upload information in the
        // dropzone target (HTML elements showing the progress) gets stored.
        this.dropzone.destroy();
    }

    displayList() {
        this.element.classList.add(this.listLayoutClass);
        this.element.classList.remove(this.gridLayoutClass);
    }

    displayGrid() {
        this.element.classList.add(this.gridLayoutClass);
        this.element.classList.remove(this.listLayoutClass);
    }

    updateSelection() {
        this.elementsOperationsTarget.innerHTML = '';

        const paths = this._getSelectedPaths();

        if (paths.length === 0) {
            this.viewStreamConnection.abortPending();

            return;
        }

        this.viewStreamConnection.get(this.listElementsOperationsUrlValue, { paths }, true);
    }

    deselectAll(event) {
        // Ignore any clicks to controls - on top, ignore clicks to labels as
        // they will fire a second time on the referenced input.
        if (event instanceof PointerEvent && event.target.closest('button, input, label')) {
            return;
        }

        for (const el of this._getSelectedElements()) {
            el.checked = false;
        }

        this.updateSelection();

        if (event instanceof KeyboardEvent) {
            event.target.blur();
        }
    }

    navigate(event) {
        const target =
            event instanceof KeyboardEvent ? event.target : event.target.closest('.element').previousElementSibling;
        this.viewStreamConnection.get(target.dataset.navigateUrl, true);
    }

    upload(event) {
        const input = event.target.closest('form')?.querySelector('input[type="file"]');

        if (!input) {
            return;
        }

        this._currentUploadUrl = TurboCable.buildURL(this.uploadOperationUrlValue, { path: input.dataset.path });

        if (input.files.length) {
            for (const file of input.files) {
                this.dropzone.addFile(file);
            }
        }

        event.target.closest('dialog')?.remove();
    }

    handleDblClickSelection(event) {
        // Fix text being selected on double/triple clicks
        if (event.detail > 1) {
            event.preventDefault();
        }
    }

    dialogTargetConnected(el) {
        el.showModal();
        el.querySelector('input')?.focus();
        el.querySelector('input[type="text"]')?.select();

        el.querySelector('form')?.addEventListener('submit', () => {
            el.remove();
        });
    }

    close(event) {
        document.getElementById(event.target.getAttribute('aria-controls')).innerText = '';
    }

    /*
     * Drag and drop works like this:
     *
     *  1) If an item from the DOM is dragged, the dragStart() function is run,
     *     where we record all element paths taking place in the drag operation.
     *     (If an item from the system (i.e. a file) is dragged, this step is
     *     bypassed.)
     *
     *  2) While dragging over drop-targets [drag{Enter|Over|Leave}()], a CSS
     *     class indicating the possibility to drop is added/removed to these
     *     targets.
     *
     *  3) When a dragged item is dropped on a drop-target, the dragDrop()
     *     function gets called, where we execute the actual file system
     *     operation (like moving or initiating an upload).
     *
     *  4) Lastly, the dragEnd() function will run, where we reset all state.
     */
    dragStart(event) {
        const target = event.target.closest('*[draggable="true"]');

        // Include the currently selected paths in the set of dragged paths if
        // the dragged element was part of the listing. This allows selecting
        // and then dragging multiple elements at once.
        this._draggedPaths = this.listingTarget.contains(target)
            ? [...new Set([...this._getSelectedPaths(), target.dataset.resource])]
            : [target.dataset.resource];

        event.dataTransfer.setData('contao/file-manager-paths', '(internal)');
        event.dataTransfer.effectAllowed = 'move';
    }

    dragEnter(event) {
        // Adding the root "dragging" class makes sure, that pointer events are
        // deactivated on all children of the drop targets. We reset this once
        // the drag-and-drop operation is completed (dragEnd).
        this.element.classList.add(this.draggingClass);

        if (this._isAllowedToDrop(event.target.dataset.resource)) {
            event.target.classList.add(this.droppingClass);
        }
    }

    dragOver(event) {
        // Keep this empty function - it still has the necessary :prevent
        // (preventDefault) set to enable dropping.
    }

    dragLeave(event) {
        event.target.classList.remove(this.droppingClass);
    }

    dragDrop(event) {
        event.target.classList.remove(this.droppingClass);

        const path = event.target.dataset.resource;

        // Move elements
        if (event.dataTransfer.getData('contao/file-manager-paths')) {
            if (this._isAllowedToDrop(path)) {
                this.actionStreamConnection.postForm(
                    this.moveOperationUrlValue,
                    { paths: this._draggedPaths },
                    { target: path },
                );
            }

            return;
        }

        // Drag and drop from external: initiate a dropzone upload
        this._currentUploadUrl = TurboStreamConnection.buildURL(this.uploadOperationUrlValue, { path });
        this.dropzone?.drop(event);
    }

    dragEnd() {
        this.element.classList.remove(this.draggingClass);
        this._draggedPaths = [];
    }

    _getElements() {
        return [...this.listingTarget.querySelectorAll('input[type="checkbox"]')];
    }

    _getSelectedElements() {
        return this._getElements().filter((el) => el.checked);
    }

    _getSelectedPaths() {
        return this._getSelectedElements().map((el) => el.dataset.resource);
    }

    _isAllowedToDrop(path) {
        return !this._draggedPaths.includes(path) && path !== this._getCommonBasePath(this._draggedPaths);
    }

    _getCommonBasePath(paths) {
        if (paths.length === 0) {
            return null;
        }

        function commonPrefixLength(a, b) {
            const minLength = Math.min(a.length, b.length);

            for (let i = 0; i < minLength; i++) {
                if (a.charAt(i) !== b.charAt(i)) {
                    return i;
                }
            }

            return minLength;
        }

        let length = paths[0].length;
        let result = paths[0];

        for (let i = 1; i < paths.length; i++) {
            const p = commonPrefixLength(result, paths[i]);

            if (p < length) {
                length = p;
            }

            if (paths[i] > result) {
                result = paths[i];
            }
        }
        return result.substring(0, result.lastIndexOf('/', length));
    }
}
