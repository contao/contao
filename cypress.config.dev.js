const { defineConfig } = require("cypress");
var platform = require('platform');

// set path to contao console via env or get it from os
const CONTAO_CONSOLE = process.env.CONTAO_CONSOLE || ('Win32' !== platform.os.family) ? 'vendor/bin/contao-console' : 'vendor\\bin\\contao-console';

module.exports = defineConfig({
    downloadsFolder: "vendor/contao/contao/tests/cypress/downloads",
    fixturesFolder: "vendor/contao/contao/tests/cypress/fixtures",
    screenshotsFolder: "vendor/contao/contao/tests/cypress/screenshots",
    videosFolder: "vendor/contao/contao/tests/cypress/videos",

    env: {
        CONTAO_CONSOLE: CONTAO_CONSOLE
    },

    e2e: {
        baseUrl: 'http://localhost:8000',
        supportFile: 'vendor/contao/contao/tests/cypress/support/e2e.js',
        specPattern: 'vendor/contao/contao/tests/cypress/e2e/**/*.cy.js',
        setupNodeEvents(on, config) {
            // implement node event listeners here
        },
    },
});
