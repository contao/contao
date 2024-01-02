import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['navigation', 'section'];

    connect () {
        this.rebuildNavigation();
        this.connected = true;
    }

    sectionTargetConnected () {
        if (!this.connected) {
            return;
        }

        this.rebuildNavigation();
    }

    rebuildNavigation () {
        if (!this.hasNavigationTarget) {
            return;
        }

        const links = document.createElement('ul');

        this.sectionTargets.forEach((el) => {
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
        });

        this.navigationTarget.replaceChildren(links);
    }
}
