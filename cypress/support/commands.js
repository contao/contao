import user from "../fixtures/users/admin.json";

Cypress.Commands.add('contaoConsole', (command, ...args) => {
    cy.exec('cypress/webspace/vendor/bin/contao-console ' + [command, ...args].join(' '));
});

Cypress.Commands.add('contaoResetSchema', () => {
    cy.contaoConsole('doctrine:schema:drop', '--force');
    cy.contaoConsole('contao:migrate', '--no-interaction', '--with-deletes', '--no-backup');
    cy.contaoConsole('contao:user:create', '--username='+user.username, '--name='+user.name, '--email='+user.email, '--password='+user.password, '--language='+user.language, '--admin');
});

Cypress.Commands.add('contaoResetFiles', () => {
    cy.exec('rm -rf "cypress/webspace/files/"');
    cy.exec('mkdir "cypress/webspace/files/"');
});
