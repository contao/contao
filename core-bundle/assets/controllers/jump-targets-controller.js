import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['navigation', 'section'];

    sectionTargetConnected() {
        this.rebuildNavigation();
    }

    sectionTargetDisconnected() {
        this.rebuildNavigation();
    }

    rebuildNavigation() {
        if (!this.hasNavigationTarget) {
            return;
        }

        const links = document.createElement('ul');

        for (const el of this.sectionTargets) {
            const action = document.createElement('button');
            action.innerText = el.getAttribute(`data-${this.identifier}-label-value`);

            action.addEventListener('click', (event) => {
                event.preventDefault();
                this.dispatch('scrollto', { target: el });
                el.scrollIntoView();
            });

            const li = document.createElement('li');
            li.append(action);

            links.append(li);
        }

        this.navigationTarget.replaceChildren(links);
    }
}
