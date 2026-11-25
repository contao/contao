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
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;

class PageMountTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testRendersPageTreeWithStringPageMounts(): void
    {
        $backend = $this->login('user_page_mount_editor');
        $backend->visit('/contao?do=page&ptg=all&state=1');

        $this->assertSame(1, $backend->page()->locator('#tl_listing')->count(), $backend->page()->locator('body')->innerText());
        $this->assertSelectorTextContains('#tl_listing', 'Page mount website');
        $this->assertSelectorTextContains('#tl_listing', 'Page mount home');
        $this->assertSame(0, $backend->page()->locator('.header_new')->count());
    }

    public function testRendersArticleTreeWithStringPageMounts(): void
    {
        $backend = $this->login('user_page_mount_editor');
        $backend->visit('/contao?do=article&ptg=all&state=1');

        $this->assertSame(1, $backend->page()->locator('#tl_listing')->count(), $backend->page()->locator('body')->innerText());
        $this->assertSelectorTextContains('#tl_listing', 'Page mount website');
        $this->assertSelectorTextContains('#tl_listing', 'Page mount home');
        $this->assertSame(0, $backend->page()->locator('.header_new')->count());
    }

    public function testHidesPagesOutsideMount(): void
    {
        $backend = $this->login('user_page_mount_manager');
        $backend->visit('/contao?do=page&ptg=all&state=1');

        $this->assertSelectorTextContains('#tl_listing', 'Page mount home');
        $this->assertSame(0, $backend->page()->locator('#tl_listing')->getByText('Main website', ['exact' => true])->count());
        $this->assertSame(0, $backend->page()->locator('#tl_listing')->getByText('Microsite', ['exact' => true])->count());
    }

    public function testHidesArticlesOutsideMount(): void
    {
        $backend = $this->login('user_page_mount_manager');
        $backend->visit('/contao?do=article&ptg=all&state=1');

        $this->assertSelectorTextContains('#tl_listing', 'Page mount article');
        $this->assertSame(0, $backend->page()->locator('#tl_listing')->getByText('Main website home', ['exact' => true])->count());
    }

    public function testDeniesDirectPageAccessOutsideMount(): void
    {
        $backend = $this->login('user_page_mount_manager');
        $backend->visit(self::managedEdition()->database()->fixtures()->interpolate('/contao?do=page&act=edit&id={page_microsite_home}'));

        $this->assertSelectorTextContains('h1', 'Forbidden');
        $this->assertSelectorTextContains('body', 'Not enough permissions');
    }

    public function testDeniesDirectArticleAccessOutsideMount(): void
    {
        $backend = $this->login('user_page_mount_manager');
        $backend->visit(self::managedEdition()->database()->fixtures()->interpolate('/contao?do=article&act=edit&id={article_main_home}'));

        $this->assertSelectorTextContains('h1', 'Forbidden');
        $this->assertSelectorTextContains('body', 'Not enough permissions');
    }

    public function testMovesPageWithinMount(): void
    {
        $backend = $this->login('user_page_mount_manager');
        $fixtures = self::managedEdition()->database()->fixtures();
        $backend->visit('/contao?do=page&ptg=all&state=1');

        $this->cutRecord($backend, 'page_string_mount_home', 'Move page');

        $backend->submitAction('Paste into page ID '.$fixtures->value('page_string_mount_destination'));
        $backend->visit('/contao?do=page&ptg=all&state=1');

        $this->assertSelectorTextContains('#tl_listing', 'Page mount home');
        $this->assertSelectorExists($fixtures->interpolate('li[data-id="{page_string_mount_destination}"] > ul > li[data-id="{page_string_mount_home}"]'));
    }

    public function testMovesArticleWithinMount(): void
    {
        $backend = $this->login('user_page_mount_manager');
        $fixtures = self::managedEdition()->database()->fixtures();
        $backend->visit('/contao?do=article&ptg=all&state=1');

        $this->cutRecord($backend, 'article_string_mount_home', 'Move article');

        $backend->submitAction('Paste into page ID '.$fixtures->value('page_string_mount_destination'));
        $backend->visit('/contao?do=article&ptg=all&state=1');

        $this->assertSelectorTextContains('#tl_listing', 'Page mount article');
        $this->assertSelectorExists($fixtures->interpolate('li[data-id="{page_string_mount_destination}"] > ul > li[data-id="{article_string_mount_home}"]'));
    }

    public function testDeniesPageMoveOutsideMount(): void
    {
        $backend = $this->login('user_page_mount_manager');
        $fixtures = self::managedEdition()->database()->fixtures();
        $backend->visit('/contao?do=page&ptg=all&state=1');
        $original = $this->treePositions($backend);

        $this->cutRecord($backend, 'page_string_mount_home', 'Move page');

        // The unmounted destination grants hierarchy permissions.
        $label = 'Paste into page ID '.$fixtures->value('page_string_mount_destination');
        $this->submitPasteTo($backend, $label, (int) $fixtures->value('page_microsite_home'));
        $this->assertSelectorTextContains('h1', 'Forbidden');

        $backend->visit('/contao?do=page&ptg=all&state=1');
        $this->assertSelectorTextContains('#tl_listing', 'Page mount home');
        $this->assertSame($original, $this->treePositions($backend));
    }

    public function testDeniesArticleMoveOutsideMount(): void
    {
        $backend = $this->login('user_page_mount_manager');
        $fixtures = self::managedEdition()->database()->fixtures();
        $backend->visit('/contao?do=article&ptg=all&state=1');
        $original = $this->treePositions($backend);

        $this->cutRecord($backend, 'article_string_mount_home', 'Move article');

        // The destination permits article hierarchy changes and has an article layout,
        // but is outside the mount.
        $label = 'Paste into page ID '.$fixtures->value('page_string_mount_destination');
        $this->submitPasteTo($backend, $label, (int) $fixtures->value('page_main_home'));
        $this->assertSelectorTextContains('h1', 'Forbidden');

        $backend->visit('/contao?do=article&ptg=all&state=1');
        $this->assertSelectorTextContains('#tl_listing', 'Page mount article');
        $this->assertSame($original, $this->treePositions($backend));
    }

    private function cutRecord(BackendBrowser $backend, string $fixture, string $operation): void
    {
        $id = self::managedEdition()->database()->fixtures()->value($fixture);

        $backend->clickButton('[data-contao--operations-menu-record-id-value="'.$id.'"] [data-contao--operations-menu-target="controller"]');
        $backend->waitFor('.operations-menu.show');
        $backend->waitForNavigation(static fn () => $backend->clickButton('.operations-menu.show button[title^="'.$operation.'"]'));
    }

    private function treePositions(BackendBrowser $backend): array
    {
        return $backend->page()->locator('#tl_listing')->evaluate('el => Array.from(el.querySelectorAll("li[data-id]"), record => [record.dataset.id, record.parentElement.closest("li[data-id]")?.dataset.id ?? null])');
    }

    private function submitPasteTo(BackendBrowser $backend, string $label, int $destination): void
    {
        $backend->waitForFullNavigation(static fn () => $backend->page()->getByRole('button', ['name' => $label])->evaluate(
            <<<'JS'
                (button, destination) => {
                    const form = button.form;
                    const url = new URL(form.action);
                    url.searchParams.set('pid', destination);
                    form.action = url.href;
                    form.submit();
                }
                JS,
            $destination,
        ));
    }
}
