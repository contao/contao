import { Application } from '@hotwired/stimulus';
import { definitionForModuleAndIdentifier, identifierForContextKey } from '@hotwired/stimulus-webpack-helpers';
import '@hotwired/turbo';
import WebAuthn from '@web-auth/webauthn-stimulus';

import './scripts/mootao.js';
import './scripts/core.js';
import './scripts/limit-height.js';
import './scripts/sectionwizard.js';

import './styles/backend.pcss';

// Start the Stimulus application
const application = Application.start();
application.debug = process.env.NODE_ENV === 'development';

// Register all controllers with `contao--` prefix
const context = require.context('./controllers', true, /\.js$/);
application.load(context.keys()
    .map((key) => {
        const identifier = identifierForContextKey(key);
        if (identifier) {
            return definitionForModuleAndIdentifier(context(key), `contao--${ identifier }`);
        }
    }).filter((value) => value)
);

application.register('contao--webauthn', WebAuthn);

// Cancel all prefetch requests that contain a request token or are related to
// the Symfony toolbar
document.documentElement.addEventListener('turbo:before-prefetch', e => {
    if (
        (e.target.search && (new URLSearchParams(e.target.search)).has('rt'))
        || e.target.classList.contains('header_back')
        || e.target.matches('[onclick^="Backend.openModalIframe("]')
        || e.target.closest('.sf-toolbar') !== null
    ) {
        e.preventDefault();
    }
});

// Make the MooTools scripts reinitialize themselves
const mooDomready = () => {
    if (!document.body.mooDomreadyFired) {
        document.body.mooDomreadyFired = true;

        if (Element.Events.removeEvents) {
            Element.Events.removeEvents();
        }

        window.fireEvent('domready');
    }
}

document.documentElement.addEventListener('turbo:render', mooDomready);
document.documentElement.addEventListener('turbo:frame-render', mooDomready);

// Always break out of a missing frame (#7501)
document.documentElement.addEventListener('turbo:frame-missing', (e) => {
    if (window.console) {
        console.warn('Turbo frame #'+e.target.id+' is missing.');
    }

    // Do not break out of frames that load their content via src
    if (e.target.hasAttribute('src')) {
        return;
    }

    e.preventDefault();
    e.detail.visit(e.detail.response);
});

// Call the beforeCache() function on all controllers implementing it. This
// allows controllers to tear down things before the page gets put into cache.
// Note that Stimulus' disconnect() function will not fire at this point and
// thus cannot be used for this task.
document.documentElement.addEventListener('turbo:before-cache', (e) => {
    application.controllers.forEach(controller => {
        if ('function' === typeof controller.beforeCache) {
            controller.beforeCache(e);
        }
    });

    // Remove the Symfony toolbar
    e.target.querySelector('.sf-toolbar')?.remove();
});
