import { Controller } from '@hotwired/stimulus';
import * as Message from '../modules/message';
import { TurboStreamConnection } from '../modules/turbo-stream-connection';

export default class extends Controller {
    #turboStreamConnection = new TurboStreamConnection();
    #runningJobs = false;
    #pollInterval = null;
    #timer = null;

    static values = {
        pendingJobsUrl: String,
        defaultInterval: Number,
        maximumInterval: Number,
        enabled: Boolean,
        allJobsProcessedMessage: String,
    };

    static targets = ['count', 'list'];

    connect() {
        this.#pollInterval = this.defaultIntervalValue;
        this.#timer = null;

        if (this.enabledValue) {
            this.enable();
        }
    }

    disconnect() {
        clearTimeout(this.#timer);
        this.#timer = null;
    }

    enable() {
        clearTimeout(this.#timer);
        this.#poll();
    }

    listTargetConnected(el) {
        // Clear timer in case the target was added manually
        clearTimeout(this.#timer);

        if ('0' === el.dataset.jobs) {
            this.countTarget.innerText = '';

            if (this.#runningJobs) {
                // ALl pending jobs have been processed
                this.#runningJobs = false;
                Message.info(this.allJobsProcessedMessageValue);
            }

            // Continuously increase interval if there are no results
            this.#pollInterval = Math.min(this.maximumIntervalValue, this.#pollInterval * 2);
        } else {
            this.countTarget.innerText = el.dataset.jobs;
            this.#runningJobs = true;

            this.#pollInterval = this.defaultIntervalValue;
        }

        this.#waitAndPoll();
    }

    #waitAndPoll() {
        this.#timer = setTimeout(() => this.#poll(), this.#pollInterval);
    }

    #poll() {
        this.#turboStreamConnection.get(this.pendingJobsUrlValue, {range: this.#pollInterval}, true);
    }
}
