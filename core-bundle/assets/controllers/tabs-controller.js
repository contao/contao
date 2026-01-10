import { Controller } from '@hotwired/stimulus';
import * as Icon from '../modules/icon';

export default class TabsController extends Controller {
    #activeTab = null;

    static values = {
        closeLabel: String,
    };

    static targets = ['navigation', 'panel'];

    panelTargetConnected(panel) {
        // When the DOM is already set up, just set the panel ID and
        // install the event listeners, otherwise create the elements first.
        const isRestore = 'tabpanel' === panel.getAttribute('role');

        const tabId = isRestore ? panel.dataset.tabId : (Math.random() + 1).toString(36).substring(7);
        const containerId = this.element.id;
        const panelReference = panel.id || `tab-panel_${containerId}_${tabId}`;
        const controlReference = `tab-control_${containerId}_${tabId}`;

        // Create navigation elements
        const selectButton = isRestore
            ? this.navigationTarget.querySelector(`button.select[aria-controls="${panelReference}"]`)
            : (() => {
                  const button = document.createElement('button');
                  button.id = controlReference;
                  button.className = 'select';
                  button.innerText = panel.dataset.label;
                  button.setAttribute('type', 'button');
                  button.setAttribute('role', 'tab');
                  button.setAttribute('aria-controls', panelReference);

                  return button;
              })();
        selectButton.addEventListener('click', () => {
            this.selectTab(panel);
        });
        selectButton.addEventListener('auxclick', (event) => {
            if (event.button === 1) {
                panel.remove();
            }
        });

        const closeButton = isRestore
            ? this.navigationTarget.querySelector(`button.close[aria-controls="${panelReference}"]`)
            : (() => {
                  const button = document.createElement('button');
                  button.className = 'close';
                  button.append(Icon.getTemplate('close', { 'aria-hidden': true, width: 12, height: 12 }).content);
                  button.setAttribute('type', 'button');
                  button.setAttribute('aria-controls', panelReference);
                  button.setAttribute('aria-label', this.closeLabelValue);

                  return button;
              })();
        closeButton.addEventListener('click', () => {
            // Remove the panel and let the disconnect handler do the rest
            panel.remove();
        });

        if (!isRestore) {
            // Enhance panel container
            panel.dataset.tabId = tabId;
            panel.id = panelReference;
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', controlReference);

            // Add navigation element
            const li = document.createElement('li');
            li.setAttribute('role', 'presentation');
            li.append(selectButton);
            li.append(closeButton);

            this.navigationTarget.append(li);
        }

        // Activate tab
        this.selectTab(panel);
    }

    panelTargetDisconnected(panel) {
        // Remove controls
        document.getElementById(panel.getAttribute('aria-labelledby'))?.parentElement?.remove();

        // Select the first tab/no tab if the current tab was active before closing.
        if (panel === this.#activeTab) {
            if (this.hasPanelTarget) {
                this.selectTab(this.panelTarget);
            } else {
                this.#activeTab = null;
            }
        }
    }

    selectTab(panel) {
        for (const el of this.panelTargets) {
            const isTarget = el === panel;

            el.toggleAttribute('aria-selected', isTarget);
            el.toggleAttribute('data-active', isTarget);
            el.style.display = isTarget ? 'revert' : 'none';

            // Re-enable/disable the button access keys
            if (isTarget) {
                for (const button of el.querySelectorAll('button[data-disabled-accesskey]')) {
                    button.setAttribute('accesskey', button.getAttribute('data-disabled-accesskey'));
                    button.removeAttribute('data-disabled-accesskey');
                }
            } else {
                for (const button of el.querySelectorAll('button[accesskey]')) {
                    button.setAttribute('data-disabled-accesskey', button.getAttribute('accesskey'));
                    button.removeAttribute('accesskey');
                }
            }

            const selectButton = document.getElementById(el.getAttribute('aria-labelledby'));
            selectButton?.toggleAttribute('aria-selected', isTarget);
            selectButton?.parentElement.toggleAttribute('data-active', isTarget);
        }

        this.#activeTab = panel;
    }

    getActiveTab() {
        return this.#activeTab;
    }

    getTabs() {
        return this.panelTargets.reduce((result, panel) => {
            result[panel.id] = panel;
            return result;
        }, {});
    }
}
