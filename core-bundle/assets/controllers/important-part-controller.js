import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['image', 'part'];

    static outlets = ['contao--input'];

    static classes = ['enabled'];

    #origin = null;
    #pointerId = null;

    connect() {
        this.update();
    }

    disconnect() {
        this.#origin = null;
        this.#pointerId = null;
    }

    contaoInputOutletConnected() {
        this.update();
    }

    contaoInputOutletDisconnected() {
        this.update();
    }

    update() {
        const inputs = this.#getInputs();

        this.element.classList.toggle(this.enabledClass, !!inputs);

        if (!inputs) {
            this.partTarget.hidden = true;
            return;
        }

        // The preview replaces the input fields
        for (const input of Object.values(inputs)) {
            input.element.closest('.widget').style.display = 'none';
        }

        const box = this.#getImageBox();
        const [x, y, width, height] = ['x', 'y', 'width', 'height'].map(
            (key) => Number.parseFloat(inputs[key].element.value) || 0,
        );

        this.partTarget.hidden = !width || !height;
        this.partTarget.style.left = `${box.left + x * box.width}px`;
        this.partTarget.style.top = `${box.top + y * box.height}px`;
        this.partTarget.style.width = `${width * box.width}px`;
        this.partTarget.style.height = `${height * box.height}px`;
    }

    start(event) {
        if (!this.#getInputs() || this.#pointerId !== null || event.button !== 0) {
            return;
        }

        this.#pointerId = event.pointerId;
        this.#origin = this.#getPosition(event);
        this.draw(event);
    }

    draw(event) {
        const inputs = this.#getInputs();

        if (!inputs || !this.#origin || event.pointerId !== this.#pointerId) {
            return;
        }

        event.preventDefault();

        const position = this.#getPosition(event);

        const values = {
            x: Math.min(this.#origin.x, position.x),
            y: Math.min(this.#origin.y, position.y),
            width: Math.abs(this.#origin.x - position.x),
            height: Math.abs(this.#origin.y - position.y),
        };

        const isEmpty = !values.width || !values.height;

        for (const [key, value] of Object.entries(values)) {
            inputs[key].element.value = isEmpty ? '' : value.toFixed(15);
        }

        this.update();
    }

    stop(event) {
        if (event.pointerId !== this.#pointerId) {
            return;
        }

        this.draw(event);
        this.#origin = null;
        this.#pointerId = null;
    }

    #getInputs() {
        const inputs = {};

        for (const key of ['x', 'y', 'width', 'height']) {
            inputs[key] = this.contaoInputOutlets.find((outlet) => outlet.element.dataset.importantPartField === key);
        }

        return Object.values(inputs).every(Boolean) ? inputs : null;
    }

    #getPosition(event) {
        const box = this.#getImageBox();
        const rect = this.element.getBoundingClientRect();
        const clamp = (value) => Math.max(0, Math.min(1, value));

        return {
            x: clamp((event.clientX - rect.left - box.left) / box.width),
            y: clamp((event.clientY - rect.top - box.top) / box.height),
        };
    }

    #getImageBox() {
        const image = this.imageTarget;
        const style = window.getComputedStyle(image);
        const padding = (side) => Number.parseFloat(style[`padding${side}`]);

        return {
            left: image.offsetLeft + image.clientLeft + padding('Left'),
            top: image.offsetTop + image.clientTop + padding('Top'),
            width: image.clientWidth - padding('Left') - padding('Right'),
            height: image.clientHeight - padding('Top') - padding('Bottom'),
        };
    }
}
