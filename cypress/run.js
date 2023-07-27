const shell = require('shelljs');
const live = require('shelljs-live');

// The manager-bundle is mirrored instead of symlinked, so the console script
// finds the correct project directory. Therefore, the folder needs to be
// removed before the Composer update.
shell.rm('-rf', 'cypress/webspace/vendor/contao/manager-bundle');

if (live(['composer', 'up', '--working-dir=cypress/webspace']) !== 0) {
  shell.exit(1);
}

if (live(['symfony', 'server:start', '--port=8765', '--dir=cypress/webspace', '--daemon']) !== 0) {
  shell.exit(1);
}

if (live(['cypress', 'run', '--config-file=cypress/cypress.config.js']) !== 0) {
  shell.exit(1);
}

live(['symfony', 'server:stop', '--dir=cypress/webspace']);
