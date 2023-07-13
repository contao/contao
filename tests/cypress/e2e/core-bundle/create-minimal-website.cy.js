import user from '../../fixtures/users/admin.json'

describe('Backend', { execTimeout: 90000 }, () => {

    before(() => {
        cy.contaoResetSchema()
        cy.contaoResetFiles()
        cy.contaoConsole(
            'contao:user:create',
            '--username='+user.username,
            '--name='+user.name,
            '--email='+user.email,
            '--password='+user.password,
            '--language='+user.language,
            '--admin',
        )
    })

    it('Create a Website', () => {
        cy.visit('/contao/login')
        cy.get('input[name=username]').type(user.username)
        cy.get('input[name=password]').type(`${user.password}{enter}`)

        cy.get('#tl_navigation a:contains(Themes)').click()
        cy.get('#tl_buttons a:contains(New)').click()
        cy.get('input[name=name]').type('Theme')
        cy.get('input[name=author]').type('Cypress')
        cy.get('button:contains(Save and close)').click()

        cy.get('a[title^="Edit the page layouts"]').click()
        cy.get('#tl_buttons a:contains(New)').click()
        cy.get('input[name=name]').type('Layout')
        cy.get('button:contains(Save and close)').click()

        cy.get('#tl_navigation a:contains(Pages)').click()

        cy.get('#tl_buttons a:contains(New)').click()
        cy.get('a[title="Paste at the top"]').click()
        cy.get('input[name=title]').type('Root Page')
        cy.get('input[name=language]').type('en-us')
        cy.get('input[name=includeLayout][type=checkbox]').check()
        cy.get('select[name=layout]').should('exist')
        cy.get('input[name=published][type=checkbox]').check()
        cy.get('input[name=fallback][type=checkbox]').check()
        cy.get('button:contains(Save and close)').click()

        cy.get('#tl_buttons a:contains(New)').click()
        cy.get('a[title^="Paste into page"]').click()
        cy.get('input[name=title]').type('Home')
        cy.get('input[name=alias]').type('index')
        cy.get('input[name=published][type=checkbox]').check()
        cy.get('button:contains(Save and close)').click()

        cy.get('#tl_navigation a:contains(Articles)').click()
        cy.get('#tl_buttons a:contains(Expand all)').click()
        cy.get('a[title^="Edit the content elements"]').click()

        cy.get('a[title="Create a new content element at the top"]').click()
        cy.get('select[name=type]').select('text', { force: true })
        cy.get('input[name="headline[value]"]').type('Headline')
        cy.get('select[name="headline[unit]"]').select('h1')
        cy.get('.tox-tinymce iframe').its('0.contentDocument.body').should('not.be.empty').then(cy.wrap).click().type('Lorem ipsum dolor sit amet.')
        cy.get('input[name=addImage][type=checkbox]').check()
        cy.get('#ft_singleSRC').click()

        simpleModal('#tl_buttons a:contains(New folder)').click()
        simpleModal('a[title="Paste into the root folder"]').click()
        simpleModal('input[name="name"]').type('images')
        simpleModal('#saveNclose').click()
        simpleModal('#tl_buttons a:contains(Upload files)').click()
        simpleModal('a[title^="Paste into folder"]').click()
        simpleModal('.dropzone.dz-clickable').selectFile('core-bundle/tests/Fixtures/images/dummy.jpg', { action: 'drag-drop' })
        simpleModal('a:contains(Go back)').click()
        simpleModal('#tl_buttons a:contains(Expand all)').click()
        simpleModal('input[value="files/images/dummy.jpg"]').click()

        cy.get('.simple-modal a:contains(Apply)').click()
        cy.get('button:contains(Save and close)').click()
        cy.get('h1:contains(Headline)').should('exist')

        cy.visit('/')
        cy.get('h1:contains(Headline)').should('exist')
        cy.get('img[src]').should('exist')
        cy.get('p:contains(Lorem ipsum)').should('exist')
    })

    function simpleModal(selector) {
        return cy.get('.simple-modal-body iframe').its('0.contentDocument.body').should('have.descendants', selector).then(cy.wrap).find(selector);
    }
})
