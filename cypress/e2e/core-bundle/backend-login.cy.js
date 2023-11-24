import user from '../../fixtures/users/admin.json';

describe('Backend', { execTimeout: 90000 }, () => {
    before(() => {
        cy.contaoResetSchema();
    });

    it('Login', () => {
        cy.visit('/contao');
        cy.url().should('match', /\/contao\/login($|\?)/);

        cy.get('input[name=username]').type(user.username);
        cy.get('input[name=password]').type(`wrong{enter}`);

        // TODO: Fix accept language header
        cy.get('.tl_error').contains(/Login failed|Anmeldung fehlgeschlagen/);

        cy.get('input[name=username]').type(user.username);
        cy.get('input[name=password]').type(`${user.password}`);
        cy.get('button[type=submit]').click();

        cy.get('h1').should('contain', 'Dashboard');

        // Our auth cookie should be present
        cy.getCookie('PHPSESSID').should('exist');

        // Our csrf_token cookie should be present
        cy.getCookie('csrf_https-contao_csrf_token').should('exist');

        // UI should reflect this user being logged in
        cy.get('ul[id="tmenu"] button').should('contain', 'User ');
        cy.get('ul[id="tmenu"] button').should('contain', user.username);
    });
});
