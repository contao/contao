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

use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;

class BackendSearchTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    protected static function createApplicationConfig(): ManagedEditionConfig
    {
        $composer = self::createMonorepoComposerConfig('calendar-bundle', 'core-bundle', 'faq-bundle', 'loupe-bridge', 'news-bundle', 'newsletter-bundle');

        $recipe = InstallationRecipe::create($composer)
            ->withFixtureFile(self::fixtureDirectory().'/'.self::FIXTURE_USERS)
            ->withFixtureFile(self::fixtureDirectory().'/'.self::FIXTURE_DEFAULT)
        ;

        return ManagedEditionConfig::create($recipe, self::projectDirectory());
    }

    public function testSearchFindsAndEditsAnAccessiblePage(): void
    {
        self::managedEdition()->runConsole(['cmsig:seal:reindex', '--drop']);

        // A short CLI worker run enables backend search for the browser request
        self::managedEdition()->runConsole(['messenger:consume', 'contao_prio_normal', '--time-limit=1']);

        $backend = $this->login();
        $backend
            ->page()
            ->locator('#backend-search')
            ->fill('Main website home')
        ;

        $result = $backend
            ->page()
            ->locator('#backend-search--results .tl_search_hit:has(.hit_edit[href*="do=page"])')
            ->filter(['hasText' => 'Main website home'])
        ;

        $result->waitFor(['state' => 'visible']);

        $this->assertSame(1, $result->count());
        $this->assertStringContainsString('Main website home', $result->locator('.hit_title')->innerText());

        $backend->waitForNavigation(static fn () => $result->locator('.hit_edit')->click());
        $backend->waitFor('input[name="title"]');

        parse_str((string) parse_url($backend->page()->url(), PHP_URL_QUERY), $query);

        $this->assertSame('page', $query['do'] ?? null);
        $this->assertSame('edit', $query['act'] ?? null);
        $this->assertSame(self::managedEdition()->database()->fixtures()->interpolate('{page_main_home}'), $query['id'] ?? null);
        $this->assertSame('Main website home', $backend->page()->locator('input[name="title"]')->inputValue());
    }
}
