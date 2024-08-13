import { Application } from '@hotwired/stimulus';
import { definitionForModuleAndIdentifier, identifierForContextKey } from '@hotwired/stimulus-webpack-helpers';
import '@hotwired/turbo';

import './scripts/mootao.js';
import './scripts/core.js';
import './scripts/limit-height.js';
import './scripts/modulewizard.js';
import './scripts/sectionwizard.js';
import './scripts/tips.js';

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

// Cancel all prefetch requests that contain a request token
document.documentElement.addEventListener('turbo:before-prefetch', e => {
    if ((new URLSearchParams(e.target.href)).has('rt') || e.target.hasAttribute('onclick') || e.target.closest('.sf-toolbar') !== null) {
        e.preventDefault();
    }
});

// Make the MooTools scripts reinitialize themselves
document.documentElement.addEventListener('turbo:render', () => {
    if (!document.body.mooDomreadyFired) {
        document.body.mooDomreadyFired = true;
        window.fireEvent('domready');
    }
});
