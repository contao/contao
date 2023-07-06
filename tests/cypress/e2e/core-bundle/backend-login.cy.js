import user from '../../fixtures/users/admin.json'

describe('Backend', { execTimeout: 90000 }, () => {

    before(() => {
        cy.exec(Cypress.env('CONTAO_CONSOLE')+' contao:migrate --no-interaction --with-deletes --no-backup')
        cy.exec(Cypress.env('CONTAO_CONSOLE')+' contao:user:create --username='+user.username+' --name='+user.name+' --email='+user.email+' --password='+user.password+' --language='+user.language+' --admin')
    })

    it('Login', () => {
        cy.visit('/contao/login')

        cy.get('input[name=username]').type(user.username)

        // {enter} causes the form to submit
        cy.get('input[name=password]').type(`${user.password}{enter}`)

        cy.get('h1').should('contain', 'Dashboard')

        // our auth cookie should be present
        cy.getCookie('PHPSESSID').should('exist')

        // our csrf_token cookie should be present
        cy.getCookie('csrf_contao_csrf_token').should('exist')

        // UI should reflect this user being logged in
        cy.get('ul[id="tmenu"] button').should('contain', 'User ')
        cy.get('ul[id="tmenu"] button').should('contain', user.username)
    })

    after(() => {
        // drop schema
        cy.exec(Cypress.env('CONTAO_CONSOLE')+' doctrine:schema:drop --force')
        cy.log('Database is back to initial state.')
    })
})
