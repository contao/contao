<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Backend;

use Contao\E2eTesting\Browser\BackendBrowser;
use Contao\E2eTesting\Browser\BrowserOptions;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Uuid;

class BackendTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testBackendLogin(): void
    {
        $backend = self::managedEdition()->createBackendBrowser();
        $backend->visit('/contao');

        $this->assertMatchesRegularExpression('#/contao/login(?:$|\?)#', $backend->page()->url());

        $backend->submitLogin('k.jones', 'kevinjones');
        $backend->waitFor('h1');

        $this->assertSelectorTextContains('h1', 'Dashboard');

        $cookies = $backend->browser()->context()->cookies();

        $this->assertNotEmpty(array_filter(
            $cookies,
            static fn (array $cookie) => 'PHPSESSID' === $cookie['name'],
        ));

        $this->assertNotEmpty(array_filter(
            $cookies,
            static fn (array $cookie) => str_ends_with($cookie['name'], 'contao_csrf_token'),
        ));

        $this->assertSelectorTextContains('#tmenu', 'k.jones');
    }

    #[DataProvider('loginLanguageProvider')]
    public function testFailedLoginUsesAcceptedLanguage(string $acceptLanguage, string $message): void
    {
        $options = BrowserOptions::create()->withAcceptLanguage($acceptLanguage);

        $backend = self::managedEdition()->createBackendBrowser(options: $options);
        $backend->visit('/contao/login');
        $backend->submitLogin('k.jones', 'wrong');

        $this->assertSelectorTextContains('.tl_error', $message);
    }

    public function testAuthenticatesAConfiguredRole(): void
    {
        $this->login('content-editor', 'backend');

        $this->assertSelectorTextContains('h1', 'Dashboard');
        $this->assertSelectorTextContains('#tmenu', 'content-editor');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function loginLanguageProvider(): iterable
    {
        yield 'German' => ['de', 'Anmeldung fehlgeschlagen'];
        yield 'English' => ['en', 'Login failed'];
    }

    public function testCreatesAMinimalWebsite(): void
    {
        $options = BrowserOptions::create()->withViewport(1440, 1200);

        $backend = $this->login(options: $options);

        $this->assertSelectorTextContains('h1', 'Dashboard');

        $this->createTheme($backend);
        $this->createLayout($backend);
        $this->createPages($backend);
        $this->createContent($backend, $this->registerDummyImage());

        $backend->visit('/');
        $backend->waitFor('h1');

        $this->assertSelectorTextContains('h1', 'Headline');
        $this->assertSelectorTextContains('p', 'Lorem ipsum dolor sit amet.');
        $this->assertSelectorExists('img[src*="dummy.jpg"]');
    }

    private function createTheme(BackendBrowser $backend): void
    {
        $backend->clickLink('Themes');
        $backend->submitNew();

        $backend->submitForm(
            'Save and close',
            [
                'name' => 'Theme',
                'author' => 'Playwright',
            ],
        );
    }

    private function createLayout(BackendBrowser $backend): void
    {
        $backend->clickTitlePrefix('Edit the page layouts');
        $backend->submitNew();
        $backend->submitForm('Save and close', ['name' => 'Layout']);
    }

    private function createPages(BackendBrowser $backend): void
    {
        $backend->clickLink('Pages');
        $backend->submitNew();
        $backend->submitAction('Paste at the top');

        $layout = self::managedEdition()
            ->database()
            ->connection()
            ->fetchOne('SELECT id FROM tl_layout WHERE name = ?', ['Layout'])
        ;

        $backend->checkAndWaitForAjax('includeLayout');
        $backend->waitFor('select[name="layout"]');
        $backend->select('layout', (string) $layout);
        $backend->check('published');
        $backend->check('fallback');

        $backend->submitForm('Save', [
            'title' => 'Root Page',
            'language' => 'en',
        ]);

        $rootPage = self::managedEdition()
            ->database()
            ->connection()
            ->fetchOne('SELECT id FROM tl_page WHERE title = ?', ['Root Page'])
        ;

        $backend->clickLink('Pages');
        $backend->submitNew();
        $backend->submitAction('Paste into page ID '.$rootPage);
        $backend->check('published');

        $backend->submitForm(
            'Save and close',
            [
                'title' => 'Home',
                'alias' => 'index',
            ],
        );
    }

    private function createContent(BackendBrowser $backend, Uuid $image): void
    {
        $backend->clickLink('Articles');
        $backend->clickButton('.header_toggle');
        $backend->clickTitlePrefix('Edit the content elements');
        $backend->submitNew();
        $backend->submitAction('Paste at the top');
        $backend->selectAndWaitForAjax('type', 'text');
        $backend->waitFor('textarea[name="text"]');
        $backend->checkAndWaitForAjax('addImage');
        $backend->waitFor('#ctrl_singleSRC');
        $backend->fillRichText('text', 'Lorem ipsum dolor sit amet.');
        $backend->selectFile('singleSRC', self::DUMMY_IMAGE, $image->toRfc4122());

        $backend->submitForm(
            'Save and close',
            [
                'headline[value]' => 'Headline',
                'headline[unit]' => 'h1',
            ],
        );
    }
}
