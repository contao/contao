// require('child_process').spawn('bash', ['cypress/run.sh'], {
//     cwd: process.cwd(),
//     detached: true,
//     stdio: 'inherit'
// });

const shell = require('shelljs');

shell.rm('-rf', 'cypress/webspace/vendor/contao/manager-bundle');

if (shell.exec('composer up --working-dir=cypress/webspace').code !== 0) {
  shell.exit(1);
}

if (shell.exec('symfony server:start --port=8765 --dir=cypress/webspace --daemon').code !== 0) {
  shell.exit(1);
}

if (shell.exec('cypress run --config-file cypress/cypress.config.js').code !== 0) {
  shell.exit(1);
}

shell.exec('symfony server:stop --dir=cypress/webspace');
