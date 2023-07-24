const { defineConfig } = require("cypress");
var platform = require('platform');

// Set the path to the Contao console via ENV or get it from the OS
const CONTAO_CONSOLE = process.env.CONTAO_CONSOLE || ('Win32' !== platform.os.family)
    ? 'tools/cypress/webspace/vendor/bin/contao-console'
    : 'tools\\cypress\\webspace\\vendor\\bin\\contao-console';

module.exports = defineConfig({
    downloadsFolder: "tests/cypress/downloads",
    fixturesFolder: "tests/cypress/fixtures",
    screenshotsFolder: "tests/cypress/screenshots",
    videosFolder: "tests/cypress/videos",
    env: {
        CONTAO_CONSOLE: CONTAO_CONSOLE
    },
    e2e: {
        baseUrl: 'https://localhost:8765',
        supportFile: 'tests/cypress/support/e2e.js',
        specPattern: 'tests/cypress/e2e/**/*.cy.js',
        setupNodeEvents(on, config) {
            // Implement node event listeners here
        },
    },
});
