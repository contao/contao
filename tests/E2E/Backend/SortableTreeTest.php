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
use Contao\InstallationRecipe\Fixture\FixtureSet;
use PHPUnit\Framework\Attributes\DataProvider;
use Playwright\Locator\LocatorInterface;

class SortableTreeTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testDragReordersRootPagesAndPersists(): void
    {
        $backend = $this->openExpandedTree();
        $tree = $backend->page()->locator('ul.tl_tree[data-id="0"]');
        $ids = $this->recordIds($tree);
        $backend->page()->locator('.header_toggle:visible')->first()->click();

        $this->drag($backend, $this->record($backend, 'page_microsite'), $this->record($backend, 'page_main_website')->locator(':scope > .tl_folder'));
        $this->waitForMove($backend);

        $this->assertSame(array_reverse($ids), $this->recordIds($tree));
        $this->assertTrue($tree->locator(':scope > li')->first()->evaluate('el => el.classList.contains("tl_folder_top")'));
        $backend->visit('/contao?do=page');
        $this->assertSame(array_reverse($ids), $this->recordIds($tree));
    }

    public function testDragMovesPageWithChildrenIntoAnEmptyPage(): void
    {
        $backend = $this->openExpandedTree();
        $page = $this->record($backend, 'page_main_home');
        $child = $this->record($backend, 'page_tree_child');
        $target = $this->record($backend, 'page_tree_empty')->locator(':scope > ul[data-contao--sortable-group-value="tree"]');

        $this->assertSame(0, $target->locator(':scope > li')->count());
        $before = (int) $child->locator(':scope > .tl_file > .tl_left')->evaluate('el => el.style.getPropertyValue("--level")');
        $this->drag($backend, $page, $target);
        $this->waitForMove($backend);

        $this->assertSame([$this->fixtureId('page_main_home')], $this->recordIds($target));
        $this->assertSame($this->fixtureId('page_main_home'), $child->evaluate('el => el.parentElement.dataset.id'));
        $this->assertSame((string) ($before + 1), $child->locator(':scope > .tl_file > .tl_left')->evaluate('el => el.style.getPropertyValue("--level")'));
        $this->assertSame('2', $page->locator(':scope > div > .tl_left')->evaluate('el => el.style.getPropertyValue("--level")'));

        $backend->visit('/contao?do=page');
        $this->expandTree($backend);
        $this->assertSame([$this->fixtureId('page_main_home')], $this->recordIds($target));
        $this->assertSame($this->fixtureId('page_main_home'), $child->evaluate('el => el.parentElement.dataset.id'));
    }

    public function testDragMovesPageBetweenPopulatedLists(): void
    {
        $backend = $this->openExpandedTree();
        $target = $this->record($backend, 'page_main_home')->locator(':scope > ul[data-contao--sortable-group-value="tree"]');

        $this->drag($backend, $this->record($backend, 'page_tree_empty'), $this->record($backend, 'page_tree_child')->locator(':scope > .tl_file'));
        $this->waitForMove($backend);

        $expected = array_map($this->fixtureId(...), ['page_tree_empty', 'page_tree_child', 'page_tree_second_child']);
        $this->assertSame($expected, $this->recordIds($target));
        $backend->visit('/contao?do=page');
        $this->expandTree($backend);
        $this->assertSame($expected, $this->recordIds($target));
    }

    public function testDragInsertsAfterAnExistingSiblingAndPersists(): void
    {
        $backend = $this->openExpandedTree();
        $list = $this->record($backend, 'page_main_website')->locator(':scope > ul[data-contao--sortable-group-value="tree"]');
        $target = $this->record($backend, 'page_tree_error')->locator(':scope > .tl_file');

        $this->drag($backend, $this->record($backend, 'page_tree_empty'), $target, after: true);
        $this->waitForMove($backend);

        $request = $backend->page()->evaluate('Object.fromEntries(new URL(window.sortingRequests[0]).searchParams)');
        $this->assertSame('1', $request['mode']);
        $this->assertSame($this->fixtureId('page_tree_error'), $request['pid']);
        $expected = array_map($this->fixtureId(...), ['page_main_home', 'page_tree_error', 'page_tree_empty']);
        $this->assertSame($expected, $this->recordIds($list));

        $backend->visit('/contao?do=page');
        $this->expandTree($backend);
        $this->assertSame($expected, $this->recordIds($list));
    }

    public function testChildListRemainsSortableAfterMovingItsParent(): void
    {
        $backend = $this->openExpandedTree();
        $parent = $this->record($backend, 'page_main_home');
        $children = $parent->locator(':scope > ul[data-contao--sortable-group-value="tree"]');
        $target = $this->record($backend, 'page_tree_empty')->locator(':scope > ul[data-contao--sortable-group-value="tree"]');

        $this->drag($backend, $parent, $target);
        $this->waitForMove($backend);
        $this->assertSame([$this->fixtureId('page_main_home')], $this->recordIds($target));
        $this->drag($backend, $this->record($backend, 'page_tree_second_child'), $this->record($backend, 'page_tree_child')->locator(':scope > .tl_file'));
        $this->waitForMove($backend, 2);

        $expected = array_map($this->fixtureId(...), ['page_tree_second_child', 'page_tree_child']);
        $this->assertSame($expected, $this->recordIds($children));
        $backend->visit('/contao?do=page');
        $this->expandTree($backend);
        $this->assertSame($this->fixtureId('page_tree_empty'), $parent->evaluate('el => el.parentElement.dataset.id'));
        $this->assertSame($expected, $this->recordIds($children));
    }

    #[DataProvider('forbiddenPageDropProvider')]
    public function testDragRejectsInvalidPageDestinations(string $source, string $destination, bool $topLevel): void
    {
        $backend = $this->openExpandedTree();
        $record = $this->record($backend, $source);
        $parent = $record->evaluate('el => el.parentElement.dataset.id');
        $target = $topLevel
            ? $backend->page()->locator('.tl_folder_top')
            : $this->record($backend, $destination)->locator(':scope > ul[data-contao--sortable-group-value="tree"]');

        $this->drag($backend, $record, $target);

        $this->assertSame($parent, $record->evaluate('el => el.parentElement.dataset.id'));
        $this->assertSame([], $backend->page()->evaluate('window.sortingRequests'));
        $backend->visit('/contao?do=page');
        $this->expandTree($backend);
        $this->assertSame($parent, $record->evaluate('el => el.parentElement.dataset.id'));
    }

    public static function forbiddenPageDropProvider(): iterable
    {
        yield 'root page into a normal page' => ['page_microsite', 'page_tree_empty', false];
        yield 'normal page into the top level' => ['page_tree_empty', '', true];
        yield 'page into its own descendant' => ['page_main_home', 'page_tree_child', false];
    }

    public function testDragReordersArticlesAndPersists(): void
    {
        $backend = $this->openExpandedTree('article');
        $articles = $this->record($backend, 'page_main_home')->locator(':scope > ul[data-contao--sortable-group-value="tl_article"]');
        $ids = $this->recordIds($articles);

        $this->drag($backend, $this->record($backend, 'article_tree_second', true), $this->record($backend, 'article_main_home', true)->locator(':scope > .tl_file'));
        $this->waitForMove($backend);

        $this->assertSame(array_reverse($ids), $this->recordIds($articles));
        $backend->visit('/contao?do=article');
        $this->expandTree($backend);
        $this->assertSame(array_reverse($ids), $this->recordIds($articles));
    }

    public function testDragMovesArticleIntoAnEmptyPage(): void
    {
        $backend = $this->openExpandedTree('article');
        $article = $this->record($backend, 'article_main_home', true);
        $target = $this->record($backend, 'page_tree_child')->locator(':scope > ul[data-contao--sortable-group-value="tl_article"]');
        $level = (int) $article->locator(':scope > .tl_file > .tl_left')->evaluate('el => el.style.getPropertyValue("--level")');

        $this->assertSame(0, $target->locator(':scope > li')->count());
        $this->drag($backend, $article, $target);
        $this->waitForMove($backend);

        $this->assertSame([$this->fixtureId('article_main_home')], $this->recordIds($target));
        $this->assertSame((string) ($level + 1), $article->locator(':scope > .tl_file > .tl_left')->evaluate('el => el.style.getPropertyValue("--level")'));
        $backend->visit('/contao?do=article');
        $this->expandTree($backend);
        $this->assertSame([$this->fixtureId('article_main_home')], $this->recordIds($target));
        $this->assertSame((string) ($level + 1), $article->locator(':scope > .tl_file > .tl_left')->evaluate('el => el.style.getPropertyValue("--level")'));
    }

    #[DataProvider('forbiddenArticleDropProvider')]
    public function testDragRejectsInvalidArticleDestinations(string $destination, string $group): void
    {
        $backend = $this->openExpandedTree('article');
        $article = $this->record($backend, 'article_main_home', true);
        $target = $this->record($backend, $destination)->locator(\sprintf(':scope > ul[data-contao--sortable-group-value="%s"]', $group));

        $this->drag($backend, $article, $target);

        $this->assertSame($this->fixtureId('page_main_home'), $article->evaluate('el => el.parentElement.dataset.id'));
        $this->assertSame([], $backend->page()->evaluate('window.sortingRequests'));
        $backend->visit('/contao?do=article');
        $this->expandTree($backend);
        $this->assertSame($this->fixtureId('page_main_home'), $article->evaluate('el => el.parentElement.dataset.id'));
    }

    public static function forbiddenArticleDropProvider(): iterable
    {
        yield 'article directly in a root page' => ['page_main_website', 'tl_article'];
        yield 'article in a page list with a different group' => ['page_main_home', 'tree'];
    }

    public function testInvalidRequestTokenRestoresTheTree(): void
    {
        $backend = $this->openExpandedTree();
        $page = $this->record($backend, 'page_main_home');
        $target = $this->record($backend, 'page_tree_empty')->locator(':scope > ul[data-contao--sortable-group-value="tree"]');
        $original = $this->recordIds($page->locator('..'));
        $levels = $page->locator('li[data-id] > div > .tl_left, :scope > div > .tl_left')->evaluateAll('labels => labels.map(el => el.style.getPropertyValue("--level"))');
        $target->evaluate('el => el.setAttribute("data-contao--sortable-request-token-value", "invalid")');

        $this->drag($backend, $page, $target);
        $backend->page()->waitForFunction('window.sortingErrors.length === 1');

        $this->assertStringContainsString('/contao/confirm', $backend->page()->evaluate('window.sortingResponses[0].redirect'));
        $this->assertSame('error', $backend->page()->evaluate('window.sortingErrors[0].type'));
        $this->assertSame($original, $this->recordIds($page->locator('..')));
        $this->assertSame($levels, $page->locator('li[data-id] > div > .tl_left, :scope > div > .tl_left')->evaluateAll('labels => labels.map(el => el.style.getPropertyValue("--level"))'));
        $backend->visit('/contao?do=page');
        $this->expandTree($backend);
        $this->assertSame($original, $this->recordIds($page->locator('..')));
        $this->assertSame($levels, $page->locator('li[data-id] > div > .tl_left, :scope > div > .tl_left')->evaluateAll('labels => labels.map(el => el.style.getPropertyValue("--level"))'));
    }

    public function testRejected404PageMoveRestoresTheTree(): void
    {
        $backend = $this->openExpandedTree();
        $page = $this->record($backend, 'page_tree_error');
        $target = $this->record($backend, 'page_tree_empty')->locator(':scope > ul[data-contao--sortable-group-value="tree"]');
        $original = $this->recordIds($page->locator('..'));

        $this->drag($backend, $page, $target);
        $this->waitForRejectedMove($backend);

        $this->assertSame($this->fixtureId('page_main_website'), $page->evaluate('el => el.parentElement.dataset.id'));
        $this->assertSame($original, $this->recordIds($page->locator('..')));
        $this->assertSame('2', $page->locator(':scope > .tl_file > .tl_left')->evaluate('el => el.style.getPropertyValue("--level")'));
        $backend->visit('/contao?do=page');
        $this->expandTree($backend);
        $this->assertSame($original, $this->recordIds($page->locator('..')));
    }

    #[DataProvider('keyboardSortingProvider')]
    public function testKeyboardSortingRetainsFocusAndPersists(string $module, string $fixture, bool $article): void
    {
        $backend = $this->openExpandedTree($module);
        $record = $this->record($backend, $fixture, $article);
        $list = $record->locator('..');
        $ids = $this->recordIds($list);
        $handle = $record->locator(':scope > div > .drag-handle');

        $handle->press('ArrowDown');
        $this->waitForMove($backend);

        $this->assertSame(array_reverse($ids), $this->recordIds($list));
        $this->assertTrue($handle->evaluate('el => el === document.activeElement'));
        $backend->visit('/contao?do='.$module);
        $this->expandTree($backend);
        $this->assertSame(array_reverse($ids), $this->recordIds($list));
    }

    public static function keyboardSortingProvider(): iterable
    {
        yield 'pages' => ['page', 'page_tree_child', false];
        yield 'articles' => ['article', 'article_main_home', true];
    }

    #[DataProvider('wrapProvider')]
    public function testKeyboardWrappingKeepsTheHeaderFirst(string $key, int $index): void
    {
        [$backend, $tree, $ids] = $this->openPageTree();

        // Keep the optimistic order visible without sending a cut request.
        $backend->page()->evaluate('() => { window.fetch = () => new Promise(() => {}); }');
        $tree->locator(':scope > li[data-id] > .tl_folder > .drag-handle')->nth($index)->press($key);

        $this->assertSame('tl_folder_top cf', $tree->locator(':scope > li')->first()->getAttribute('class'));
        $this->assertSame(array_reverse($ids), $this->recordIds($tree));
    }

    public static function wrapProvider(): iterable
    {
        yield 'first record up' => ['ArrowUp', 0];
        yield 'last record down' => ['ArrowDown', 1];
    }

    #[DataProvider('rollbackProvider')]
    public function testFailedKeyboardMoveRestoresTheOriginalPosition(bool $pressTab): void
    {
        [$backend, $tree, $ids] = $this->openPageTree();

        // Resolve the failed request only after the intervening keydown.
        $backend->page()->evaluate(<<<'JS'
            () => {
                window.fetch = () => new Promise(resolve => {
                    window.failSorting = () => resolve(new Response('', {status: 403}));
                });
            }
            JS);

        $handle = $tree->locator(\sprintf(':scope > li[data-id="%s"] > .tl_folder > .drag-handle', $ids[0]));
        $handle->press('ArrowDown');
        $this->assertSame(array_reverse($ids), $this->recordIds($tree));

        if ($pressTab) {
            $handle->press('Tab');
        }

        $backend->page()->evaluate(<<<'JS'
            () => new Promise(resolve => {
                document.addEventListener('contao--message', () => resolve(), {once: true});
                window.failSorting();
            })
            JS);

        $this->assertSame($ids, $this->recordIds($tree));
    }

    public static function rollbackProvider(): iterable
    {
        yield 'without another key' => [false];
        yield 'Tab while pending' => [true];
    }

    public function testRemovingTheControllerDestroysSortable(): void
    {
        [, $tree] = $this->openPageTree();

        // Sortable stores its instance on the list. Retain it to observe cleanup after
        // Stimulus processes removal of the controller identifier.
        $this->assertTrue($tree->evaluate(<<<'JS'
            el => {
                window.treeSortable = Object.entries(el).find(([key, value]) => key.startsWith('Sortable') && value?.el === el)?.[1];
                return Boolean(window.treeSortable);
            }
            JS));

        $tree->evaluate(<<<'JS'
            async (el) => {
                el.dataset.controller = el.dataset.controller.split(/\s+/).filter(value => value !== 'contao--sortable').join(' ');
                await new Promise(resolve => setTimeout(resolve, 0));
            }
            JS);

        $this->assertTrue($tree->evaluate('el => el.isConnected'));
        $this->assertTrue($tree->evaluate('() => window.treeSortable.el === null'));
    }

    /**
     * @return array{BackendBrowser, LocatorInterface, list<string>}
     */
    private function openPageTree(): array
    {
        $backend = $this->login();
        $backend->visit('/contao?do=page');

        $tree = $backend->page()->locator('ul.tl_tree[data-id="0"]');
        $tree->waitFor(['state' => 'visible']);
        $tree->locator(':scope > li[data-id] > .tl_folder > .drag-handle')->nth(1)->waitFor(['state' => 'visible']);

        $ids = $this->recordIds($tree);
        $this->assertCount(2, $ids);

        return [$backend, $tree, $ids];
    }

    /**
     * @return list<string>
     */
    private function recordIds(LocatorInterface $tree): array
    {
        return $tree->locator(':scope > li[data-id]')->evaluateAll('items => items.map(item => item.dataset.id)');
    }

    /**
     * Sets up the testing and expands the tree.
     */
    private function openExpandedTree(string $module = 'page'): BackendBrowser
    {
        self::managedEdition()->resetDatabase(new FixtureSet([
            self::fixtureDirectory().'/users.yaml',
            self::fixtureDirectory().'/default.yaml',
            self::fixtureDirectory().'/sortable_tree.yaml',
        ]));

        $backend = $this->login();
        $backend->visit('/contao?do='.$module);
        $this->assertSame(1, $backend->page()->locator('ul.tl_listing[data-id="0"]')->count(), $backend->page()->locator('body')->innerText());
        $this->expandTree($backend);
        $backend->page()->evaluate(<<<'JS'
            () => {
                window.sortingResponses = [];
                window.sortingRequests = [];
                window.sortingErrors = [];
                window.sortingDrags = 0;
                document.addEventListener('dragstart', () => window.sortingDrags++);
                document.addEventListener('contao--message', event => window.sortingErrors.push(event.detail));
                const originalFetch = window.fetch;
                window.fetch = async (...args) => {
                    const isCut = new URL(args[0], location.href).searchParams.get('act') === 'cut';
                    if (isCut) window.sortingRequests.push(String(args[0]));
                    const response = await originalFetch(...args);
                    if (isCut) {
                        window.sortingResponses.push({status: response.status, redirect: response.headers.get('X-Ajax-Location')});
                    }
                    return response;
                };
            }
            JS);

        return $backend;
    }

    private function expandTree(BackendBrowser $backend): void
    {
        $backend->waitFor('ul.tl_listing[data-id="0"]');
        $operation = $backend->page()->locator('.header_toggle:visible')->first();

        if (str_contains($operation->innerText(), 'Collapse')) {
            $operation->click();
        }

        $operation->click();
        $this->record($backend, 'page_tree_child')->waitFor(['state' => 'visible']);
    }

    private function fixtureId(string $fixture): string
    {
        return (string) self::managedEdition()->database()->fixtures()->value($fixture);
    }

    private function record(BackendBrowser $backend, string $fixture, bool $article = false): LocatorInterface
    {
        return $backend->page()->locator(\sprintf('ul.tl_listing li[data-id="%s"]%s', $this->fixtureId($fixture), $article ? '[data-leaf-record]' : ':not([data-leaf-record])'));
    }

    private function drag(BackendBrowser $backend, LocatorInterface $record, LocatorInterface $target, bool $after = false): void
    {
        $handle = $record->locator(':scope > div > .drag-handle');
        $drags = $backend->page()->evaluate('window.sortingDrags');

        if ($target->evaluate('el => el.getBoundingClientRect().height > 0')) {
            $y = $after ? $target->evaluate('el => el.getBoundingClientRect().height - 8') : 8;
            $handle->dragTo($target, ['targetPosition' => ['x' => 80, 'y' => $y]]);
            $this->assertSame($drags + 1, $backend->page()->evaluate('window.sortingDrags'));

            return;
        }

        $this->startDrag($backend, $record);
        $mouse = $backend->page()->mouse();

        // Approach a zero-height list from its empty-insert margin so the next siblings
        // row does not handle the dragover as a reorder in the source list.
        for ($step = 0; $step < 2; ++$step) {
            $end = $target->evaluate('el => { const r = el.getBoundingClientRect(); return {x: r.left - 2, y: r.top + r.height / 2}; }');
            $mouse->move($end['x'] + $step, $end['y']);
            $backend->page()->evaluate('() => new Promise(resolve => setTimeout(resolve, 150))');
        }

        $mouse->up();

        $this->assertSame($drags + 1, $backend->page()->evaluate('window.sortingDrags'));
    }

    private function startDrag(BackendBrowser $backend, LocatorInterface $record): void
    {
        $handle = $record->locator(':scope > div > .drag-handle');
        $handle->scrollIntoViewIfNeeded();

        $start = $handle->boundingBox();
        $mouse = $backend->page()->mouse();
        $mouse->move($start['x'] + $start['width'] / 2, $start['y'] + $start['height'] / 2);
        $mouse->down();
        $mouse->move($start['x'] + $start['width'] / 2 + 10, $start['y'] + $start['height'] / 2, ['steps' => 5]);
        $backend->page()->waitForFunction('id => document.querySelector(".sortable-ghost")?.dataset.id === id', $record->getAttribute('data-id'));
    }

    private function waitForMove(BackendBrowser $backend, int $count = 1): void
    {
        $backend->page()->waitForFunction('window.sortingResponses.length === '.$count);
        $responses = $backend->page()->evaluate('window.sortingResponses');
        $this->assertSame(303, $responses[$count - 1]['status']);
        $this->assertNotEmpty($responses[$count - 1]['redirect']);
        $this->assertSame([], $backend->page()->evaluate('window.sortingErrors'));
        $backend->page()->waitForFunction('Array.from(document.querySelectorAll("ul.tl_listing li[data-id]")).every(el => el.getAnimations().length === 0)');
    }

    private function waitForRejectedMove(BackendBrowser $backend, int $index = 0): void
    {
        $backend->page()->waitForFunction('window.sortingErrors.length === 1');
        $this->assertSame(403, $backend->page()->evaluate('window.sortingResponses['.$index.'].status'));
        $this->assertSame('error', $backend->page()->evaluate('window.sortingErrors[0].type'));
        $this->assertNotEmpty($backend->page()->evaluate('window.sortingErrors[0].message'));
    }
}
