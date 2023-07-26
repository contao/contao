const { defineConfig } = require("cypress");
const platform = require('platform');

// Set the path to the Contao console via ENV or get it from the OS
const CONTAO_CONSOLE = process.env.CONTAO_CONSOLE || ('Win32' !== platform.os.family)
    ? 'cypress/webspace/vendor/bin/contao-console'
    : 'cypress\\webspace\\vendor\\bin\\contao-console';

module.exports = defineConfig({
    downloadsFolder: "cypress/downloads",
    fixturesFolder: "cypress/fixtures",
    screenshotsFolder: "cypress/screenshots",
    videosFolder: "cypress/videos",
    env: {
        CONTAO_CONSOLE: CONTAO_CONSOLE
    },
    e2e: {
        baseUrl: 'https://localhost:8765',
        supportFile: 'cypress/support/e2e.js',
        specPattern: 'cypress/e2e/**/*.cy.js',
        setupNodeEvents(on, config) {
            // Implement node event listeners here
        },
    },
});
