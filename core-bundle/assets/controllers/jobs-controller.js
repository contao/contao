import { Controller } from '@hotwired/stimulus';
import * as Message from '../modules/message';
import { TurboStreamConnection } from '../modules/turbo-stream-connection';

export default class extends Controller {
    #turboStreamConnection = new TurboStreamConnection();
    #runningJobs = false;
    #pollInterval = null;
    #timer = null;
    #etag = null;
    #connected = false;
    #lastPollStartedAt = null;

    static values = {
        pendingJobsUrl: String,
        defaultInterval: Number,
        maximumInterval: Number,
        enabled: Boolean,
        allJobsProcessedMessage: String,
    };

    static targets = ['count', 'list'];

    connect() {
        this.#connected = true;
        this.#pollInterval = this.defaultIntervalValue;
        this.#timer = null;
        this.#etag = null;
        this.#lastPollStartedAt = Date.now();

        if (this.enabledValue || this.hasListTarget) {
            this.enable();
        }
    }

    disconnect() {
        this.#connected = false;
        clearTimeout(this.#timer);
        this.#timer = null;
        this.#turboStreamConnection.abortPending();
    }

    enable() {
        clearTimeout(this.#timer);
        this.#timer = null;
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
        if (!this.#connected) {
            return;
        }

        clearTimeout(this.#timer);
        this.#timer = setTimeout(() => {
            this.#timer = null;
            this.#poll();
        }, this.#pollInterval);
    }

    async #poll() {
        if (!this.#connected) {
            return;
        }

        const startedAt = Date.now();

        // Include response time and delayed timers so completed jobs are not missed
        const range = Math.max(this.#pollInterval, startedAt - this.#lastPollStartedAt);
        const result = await this.#turboStreamConnection.get(
            this.pendingJobsUrlValue,
            { range },
            true,
            this.#etag ? { 'If-None-Match': this.#etag } : {},
        );

        if (!this.#connected || result.aborted) {
            return;
        }

        if (result.ok) {
            this.#lastPollStartedAt = startedAt;
        }

        this.#etag = result.response?.headers.get('etag') || this.#etag;

        // If no Turbo stream update happened (e.g. 204 no changes), schedule
        // the next poll here.
        if (!this.#timer) {
            this.#waitAndPoll();
        }
    }
}
