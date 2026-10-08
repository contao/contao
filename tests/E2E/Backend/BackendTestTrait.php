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
use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\InstallationRecipe\File\FileMapping;
use Contao\InstallationRecipe\Fixture\FixtureSet;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;
use Symfony\Component\Uid\Uuid;

trait BackendTestTrait
{
    private const string DUMMY_IMAGE = 'files/images/dummy.jpg';

    private const string FIXTURE_USERS = 'users.yaml';

    private const string FIXTURE_DEFAULT = 'default.yaml';

    private const string DCA_CONTENT = 'tl_content.php';

    protected static function createApplicationConfig(): ManagedEditionConfig
    {
        $composer = self::createMonorepoComposerConfig(
            'calendar-bundle',
            'core-bundle',
            'faq-bundle',
            'news-bundle',
            'newsletter-bundle',
        );

        $dummyImage = self::projectDirectory().'/core-bundle/tests/Fixtures/images/dummy.jpg';

        $recipe = InstallationRecipe::create($composer)
            ->withFixtureFile(self::fixtureDirectory().'/'.self::FIXTURE_USERS)
            ->withFixtureFile(self::fixtureDirectory().'/'.self::FIXTURE_DEFAULT)
            ->withFileMapping(new FileMapping($dummyImage, self::DUMMY_IMAGE))
            ->withFileMapping(new FileMapping($dummyImage, 'files/media/dummy.jpg'))
            ->withFileMapping(new FileMapping($dummyImage, 'files/private/dummy.jpg'))
        ;

        return ManagedEditionConfig::create($recipe, self::projectDirectory())
            ->withDcaFile(self::customDcaDirectory().'/'.self::DCA_CONTENT)
        ;
    }

    private static function fixtureDirectory(): string
    {
        return self::projectDirectory().'/tests/E2E/Fixtures/Backend';
    }

    private static function customDcaDirectory(): string
    {
        return self::projectDirectory().'/tests/E2E/Fixtures/Dca';
    }

    /**
     * Synchronizes a mapped file into tl_files and returns its UUID.
     */
    private function registerDummyImage(string $path = self::DUMMY_IMAGE): Uuid
    {
        self::managedEdition()->synchronizeFiles($path);

        $uuid = self::managedEdition()
            ->database()
            ->connection()
            ->fetchOne('SELECT uuid FROM tl_files WHERE path = ?', [$path])
        ;

        if (!\is_string($uuid)) {
            throw new \LogicException(\sprintf('Could not find the synchronized file "%s".', $path));
        }

        return Uuid::fromBinary($uuid);
    }

    private function login(string $username = 'k.jones', string $password = 'kevinjones', BrowserOptions|null $options = null): BackendBrowser
    {
        $backend = self::managedEdition()->createBackendBrowser(options: $options);
        $backend->visit('/contao/login');
        $backend->submitLogin($username, $password);
        $backend->waitFor('h1');

        return $backend;
    }

    /**
     * Logs in and opens the content elements of the article fixture.
     *
     * @return array{BackendBrowser, string}
     */
    private function openArticle(): array
    {
        $fixtures = self::managedEdition()->prepareDatabase(new FixtureSet([
            self::fixtureDirectory().'/'.self::FIXTURE_USERS,
            self::fixtureDirectory().'/'.self::FIXTURE_DEFAULT,
        ]));

        $backend = $this->login();

        $articleUrl = $fixtures->interpolate('/contao?do=article&table=tl_content&id={article_main_home}');
        $backend->visit($articleUrl);

        return [$backend, $articleUrl];
    }

    /**
     * Puts a record into the clipboard via its operations menu (Move or Copy).
     */
    private function clipboard(BackendBrowser $backend, string $record, string $operation): void
    {
        $backend->clickButton($record.' [data-contao--operations-menu-target="controller"]');
        $backend->waitFor('.operations-menu.show');
        $backend->waitForNavigation(static fn () => $backend->clickButton(\sprintf('.operations-menu.show button[title^="%s content element"]', $operation)));
    }

    /**
     * Asserts whether an enabled paste button (PASTE_AFTER or PASTE_INTO) exists in
     * the given container. Paste at the top uses the PASTE_AFTER icon.
     */
    private function assertPasteButton(BackendBrowser $backend, string $container, string $icon, bool $enabled): void
    {
        $this->assertSame($enabled, $backend->page()->locator(\sprintf('%s button img[src*="/%s."]', $container, $icon))->count() > 0);
    }

    private function createElementGroup(BackendBrowser $backend): void
    {
        $backend->submitNew();
        $backend->submitAction('Paste at the top');

        // Changing the type auto-submits the form so we wait until the text palette is gone
        $backend->select('type', 'element_group');
        $backend->page()->locator('textarea[name="text"]')->waitFor(['state' => 'detached']);
        $backend->submitForm('Save and close');

        $this->assertSelectorTextContains('.cte_type', 'Element group');
    }

    private function createTextElement(BackendBrowser $backend, string $text = 'Lorem ipsum'): void
    {
        $backend->submitNew();
        $backend->submitAction('Paste at the top');
        $backend->waitFor('textarea[name="text"]');
        $backend->fillRichText('text', $text);
        $backend->submitForm('Save and close');
    }
}
