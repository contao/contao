import { Controller } from '@hotwired/stimulus';

export default class ClipboardController extends Controller {
    static instances = [];
    static classes = ['written'];
    static values = {
        content: String
    }

    connect() {
        if (!ClipboardController.instances.contains(this)) {
            ClipboardController.instances.push(this);
        }
    }

    disconnect() {
        ClipboardController.instances = ClipboardController.instances.filter(v => v !== this);
    }

    write () {
        navigator.clipboard
            .writeText(this.contentValue)
            .then(() => {
                // (Re-) add "written" class to give a visual feedback
                this.reset();

                requestAnimationFrame(() => {
                    this.element.classList.add(this.writtenClass);
                });

                // Notify others
                ClipboardController.instances
                    .filter(i => i !== this)
                    .forEach(i => i.reset())
                ;
            })
        ;
    }

    reset() {
        this.element.classList.remove(this.writtenClass);
    }
}
