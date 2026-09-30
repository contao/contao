<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Backend\ContentElement;

use Contao\E2eTesting\Browser\BackendBrowser;
use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\InstallationRecipe\Fixture\FixtureSet;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;

trait ContentElementTestTrait
{
    protected static function createManagedEditionConfig(): ManagedEditionConfig
    {
        $recipe = InstallationRecipe::create(self::createMonorepoComposerConfig('core-bundle'))
            ->withFixtureFile(self::fixtureDirectory().'/users.yaml')
        ;

        return ManagedEditionConfig::create($recipe, self::projectDirectory());
    }

    private static function fixtureDirectory(): string
    {
        return self::projectDirectory().'/core-bundle/tests/Fixtures/Functional/Backend';
    }

    /**
     * Logs in and opens the content elements of the article fixture.
     *
     * @return array{BackendBrowser, string}
     */
    private function openArticle(): array
    {
        $fixtures = self::managedEdition()->prepareDatabase(new FixtureSet([
            self::fixtureDirectory().'/users.yaml',
            // Update fixtureDirectory after #10353 has been merged but low Prio ¯\_(ツ)_/¯
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/article.yaml',
        ]));

        $backend = self::managedEdition()->createBackendBrowser();
        $backend->visit('/contao/login');
        $backend->submitLogin('k.jones', 'kevinjones');
        $backend->waitFor('h1');

        $articleUrl = $fixtures->interpolate('/contao?do=article&table=tl_content&id={article}');
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
