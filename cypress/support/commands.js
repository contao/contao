// See https://on.cypress.io/custom-commands

Cypress.Commands.add('contaoConsole', (command, ...args) => {
    cy.exec(Cypress.env('CONTAO_CONSOLE') + ' ' + [command, ...args].join(' '))
})

Cypress.Commands.add('contaoResetSchema', () => {
    cy.contaoConsole('doctrine:schema:drop', '--force')
    cy.contaoConsole('contao:migrate', '--no-interaction', '--with-deletes', '--no-backup')
})

Cypress.Commands.add('contaoResetFiles', () => {
    cy.exec('rm -rf "cypress/webspace/files/"')
    cy.exec('mkdir "cypress/webspace/files/"')
})
