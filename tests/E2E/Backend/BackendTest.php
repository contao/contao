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

use Contao\E2eTestBundle\Browser\BackendBrowser;
use Contao\E2eTestBundle\Browser\BrowserOptions;
use Contao\E2eTestBundle\ManagedEdition\ManagedEditionConfig;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\InstallationRecipe\File\FileMapping;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Uuid;

class BackendTest extends AbstractContaoMonorepoE2ETestCase
{
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
        $backend = self::managedEdition()->createBackendBrowser(options: $options);
        $backend->visit('/contao/login');
        $backend->submitLogin('k.jones', 'kevinjones');
        $backend->waitFor('h1');
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

    protected static function createManagedEditionConfig(): ManagedEditionConfig
    {
        $composer = self::createMonorepoComposerConfig('core-bundle');
        $recipe = InstallationRecipe::create($composer)
            ->withFixtureFile(self::fixtureDirectory().'/users.yaml')
            ->withFileMapping(new FileMapping(
                self::projectDirectory().'/core-bundle/tests/Fixtures/images/dummy.jpg',
                'files/images/dummy.jpg',
            ))
        ;

        return ManagedEditionConfig::create($recipe, self::projectDirectory());
    }

    private static function fixtureDirectory(): string
    {
        return self::projectDirectory().'/core-bundle/tests/Fixtures/Functional/Backend';
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

        $layout = self::managedEdition()->database()->connection()->fetchOne('SELECT id FROM tl_layout WHERE name = ?', ['Layout']);
        $backend->checkAndWaitForAjax('includeLayout');
        $backend->waitFor('select[name="layout"]');
        $backend->select('layout', (string) $layout);
        $backend->check('published');
        $backend->check('fallback');
        $backend->submitForm('Save', [
            'title' => 'Root Page',
            'language' => 'en',
        ]);
        $backend->clickLink('Pages');
        $backend->submitNew();
        $backend->submitAction('Paste into page');
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
        $backend->selectFile('singleSRC', 'files/images/dummy.jpg', $image->toRfc4122());
        $backend->submitForm(
            'Save and close',
            [
                'headline[value]' => 'Headline',
                'headline[unit]' => 'h1',
            ],
        );
    }

    private function registerDummyImage(): Uuid
    {
        self::managedEdition()->synchronizeFiles('files/images/dummy.jpg');
        $uuid = self::managedEdition()->database()->connection()->fetchOne(
            'SELECT uuid FROM tl_files WHERE path = ?',
            ['files/images/dummy.jpg'],
        );

        if (!\is_string($uuid)) {
            throw new \LogicException('Could not find the synchronized dummy image.');
        }

        return Uuid::fromBinary($uuid);
    }
}
