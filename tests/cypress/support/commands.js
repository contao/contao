// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

Cypress.Commands.add('contaoConsole', (command, ...args) => {
    cy.exec(Cypress.env('CONTAO_CONSOLE') + ' ' + [command, ...args].join(' '))
})

Cypress.Commands.add('contaoResetSchema', () => {
    cy.contaoConsole('doctrine:schema:drop', '--force')
    cy.contaoConsole('contao:migrate', '--no-interaction', '--with-deletes', '--no-backup')
})

Cypress.Commands.add('contaoResetFiles', () => {
    cy.exec('rm -rf webspace/files/')
    cy.exec('mkdir webspace/files/')
})
