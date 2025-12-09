import { Controller } from '@hotwired/stimulus';
import * as Message from '../modules/message';
import { TurboStreamConnection } from '../modules/turbo-stream-connection';

export default class extends Controller {
    _turboStreamConnection = new TurboStreamConnection();
    _runningJobs = false;

    static values = {
        pendingJobsUrl: String,
        defaultInterval: Number,
        maximumInterval: Number,
        enabled: Boolean,
        allJobsProcessedMessage: String,
    };

    static targets = ['count', 'list'];

    connect() {
        this._pollInterval = this.defaultIntervalValue;
        this._timer = null;

        if (this.enabledValue) {
            this.enable();
        }
    }

    disconnect() {
        clearTimeout(this._timer);
        this._timer = null;
    }

    enable() {
        clearTimeout(this._timer);
        this._poll();
    }

    listTargetConnected(el) {
        // Clear timer in case the target was added manually
        clearTimeout(this._timer);

        if ('0' === el.dataset.jobs) {
            this.countTarget.innerText = '';

            if (this._runningJobs) {
                // ALl pending jobs have been processed
                this._runningJobs = false;
                Message.info(this.allJobsProcessedMessageValue);
            }

            // Continuously increase interval if there are no results
            this._pollInterval = Math.min(this.maximumIntervalValue, this._pollInterval * 2);
        } else {
            this.countTarget.innerText = el.dataset.jobs;
            this._runningJobs = true;

            this._pollInterval = this.defaultIntervalValue;
        }

        this._waitAndPoll();
    }

    _waitAndPoll() {
        this._timer = setTimeout(() => this._poll(), this._pollInterval);
    }

    _poll() {
        this._turboStreamConnection.get(this.pendingJobsUrlValue, null, true);
    }
}
