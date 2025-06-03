import { Controller } from '@hotwired/stimulus';
import { TurboStreamConnection } from '../modules/turbo-stream-connection';

export default class extends Controller {
    _turboStreamConnection = new TurboStreamConnection();
    _runningJobs = false;

    static values = {
        pendingJobsUrl: String,
        defaultInterval: Number,
        maximumInterval: Number,
        enabled: Boolean,
    };

    static targets = ['count', 'list'];

    connect() {
        this._pollInterval = this.defaultIntervalValue;
        this._timer = null;

        if (this.enabledValue) {
            this.enable();
        }
    }

    enable() {
        clearTimeout(this._timer);
        this._poll();
    }

    listTargetConnected(el) {
        // Clear timer in case the target was added manually
        clearTimeout(this._timer);

        if (el.dataset.jobs === '0') {
            this.countTarget.innerText = '';

            if (this._runningJobs) {
                this._runningJobs = false;
                this._allJobsProcessed();
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

    _allJobsProcessed() {
        // todo: replace with flash message once the functionality from #8204 has been merged
        console.log('All jobs have been processed.');
    }

    _waitAndPoll() {
        this._timer = setTimeout(() => this._poll(), this._pollInterval);
    }

    _poll() {
        this._turboStreamConnection.get(this.pendingJobsUrlValue, null, true);
    }
}
