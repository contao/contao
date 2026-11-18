import { Controller } from '@hotwired/stimulus';
import { PageSnapshot, session } from '@hotwired/turbo';

export default class extends Controller {
    // Turbo reloads the page if a 4xx form response has the "turbo-visit-control=reload"
    // meta tag, so render the version conflict page the same way it renders a 5xx response.
    async render(event) {
        const response = event.detail.fetchResponse;

        if (409 !== response.statusCode) {
            return;
        }

        event.preventDefault();

        const snapshot = PageSnapshot.fromHTMLString(await response.responseHTML);

        await session.view.renderError(snapshot, session.navigator.currentVisit);
        session.view.clearSnapshotCache();
    }
}
