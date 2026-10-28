<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Backend\Widget;

use Contao\E2eTesting\Browser\BackendBrowser;
use Contao\E2eTesting\Http\HttpBrowserOptions;
use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\E2eTests\Backend\BackendTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Playwright\Locator\LocatorInterface;
use Symfony\Component\DomCrawler\Crawler;

class ListWizardTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait {
        createApplicationConfig as private createBackendApplicationConfig;
    }

    private const string NESTED = 'listitems';

    private const string BASIC_LIST_WIZARD = 'list_wizard_basic';

    private const array FIXTURE = ['Foo', '-Bar', '-Baz', 'Qux', 'Quux'];

    #[DataProvider('operationProvider')]
    public function testOperationsChangeTheNesting(int $row, string $button, array $expected): void
    {
        $backend = $this->openListElement();

        $this->rows($backend)->nth($row)->locator(':scope > '.$button)->click();

        $this->assertSame($expected, $this->tree($backend));

        $backend->submitForm('Save');

        $this->assertSame($expected, $this->tree($backend));
    }

    public static function operationProvider(): iterable
    {
        yield 'indent an item' => [3, '.indent', ['Foo', '-Bar', '-Baz', '-Qux', 'Quux']];
        yield 'outdent an item with following siblings' => [1, '.outdent', ['Foo', 'Bar', '-Baz', 'Qux', 'Quux']];
        yield 'outdent the last child' => [2, '.outdent', ['Foo', '-Bar', 'Baz', 'Qux', 'Quux']];
        yield 'copy an item with its children' => [0, '[data-action^="contao--list-wizard#copy"]', ['Foo', '-Bar', '-Baz', 'Foo', '-Bar', '-Baz', 'Qux', 'Quux']];
        yield 'delete an item and keep its children' => [0, '[data-action^="contao--list-wizard#delete"]', ['Bar', 'Baz', 'Qux', 'Quux']];
        yield 'delete an item' => [4, '[data-action^="contao--list-wizard#delete"]', ['Foo', '-Bar', '-Baz', 'Qux']];
    }

    public function testIndentAndOutdentButtonsAreOnlyEnabledWhenPossible(): void
    {
        $backend = $this->openListElement();

        // [indent, outdent] for each item: the first item of a list cannot be indented
        // and root items cannot be outdented
        $expected = [[false, false], [false, true], [true, true], [true, false], [true, false]];

        $this->assertSame($expected, $this->buttonStates($backend));

        $this->rows($backend)->nth(3)->locator(':scope > .indent')->click();

        $this->assertSame([[false, false], [false, true], [true, true], [true, true], [true, false]], $this->buttonStates($backend));
    }

    public function testArrowKeysIndentOutdentAndMoveBetweenItems(): void
    {
        $backend = $this->openListElement();
        $keyboard = $backend->page()->keyboard();

        // At the end of the text, ArrowRight indents the item and keeps editing it
        $this->activate($backend, 3);
        $this->moveCaret($backend, true);

        $keyboard->press('ArrowRight');

        $this->assertSame(['Foo', '-Bar', '-Baz', '-Qux', 'Quux'], $this->tree($backend));

        $this->waitForEditor($backend, 3);

        // At the start of the text, ArrowLeft outdents it again
        $this->moveCaret($backend, false);

        $keyboard->press('ArrowLeft');

        $this->assertSame(self::FIXTURE, $this->tree($backend));

        $this->waitForEditor($backend, 3);

        // ArrowUp and ArrowDown move to the previous or next item across the levels
        $this->moveCaret($backend, false);

        $keyboard->press('ArrowUp');

        $this->waitForEditor($backend, 2);

        $this->moveCaret($backend, true);

        $keyboard->press('ArrowDown');

        $this->waitForEditor($backend, 3);

        // Inside the text, the caret moves as usual
        $this->moveCaret($backend, true);

        $keyboard->press('ArrowLeft');
        $keyboard->type('X');

        $backend->submitForm('Save');

        $this->assertSame(['Foo', '-Bar', '-Baz', '<p>QuXx</p>', 'Quux'], $this->tree($backend));
    }

    public function testEnterAddsAnItemInsteadOfSubmittingTheForm(): void
    {
        $backend = $this->openListElement();
        $url = $backend->page()->url();

        $this->activate($backend, 1);
        $this->moveCaret($backend, true);

        $backend->page()->keyboard()->press('Enter');

        $this->waitForEditor($backend, 2);

        $backend->page()->keyboard()->type('Corge');

        // Shift+Enter adds a line break instead of an item
        $backend->page()->keyboard()->press('Shift+Enter');
        $backend->page()->keyboard()->type('Grault');

        $this->assertSame($url, $backend->page()->url());
        $this->assertCount(6, $this->tree($backend));

        $backend->submitForm('Save');

        $this->assertSame(['Foo', '-Bar', '-<p>Corge<br>Grault</p>', '-Baz', 'Qux', 'Quux'], $this->tree($backend));
    }

    public function testModifierKeysAndSelectionsAreLeftToTheBrowser(): void
    {
        $backend = $this->openListElement();
        $keyboard = $backend->page()->keyboard();

        // Extending the selection does not indent or outdent
        $this->activate($backend, 3);
        $this->moveCaret($backend, true);

        $keyboard->press('Shift+ArrowRight');

        $this->activate($backend, 1);
        $this->moveCaret($backend, false);

        $keyboard->press('Shift+ArrowLeft');

        $this->assertSame(self::FIXTURE, $this->tree($backend));

        // A selection is collapsed instead of outdenting the item
        $backend->page()->evaluate('() => { const editor = hugerte.activeEditor; editor.selection.select(editor.getBody(), true) }');
        $keyboard->press('ArrowLeft');

        $this->assertSame(self::FIXTURE, $this->tree($backend));

        // Confirming an IME composition does not add an item
        $this->editor($backend, 1)->evaluate('el => el.dispatchEvent(new KeyboardEvent("keydown", {key: "Enter", isComposing: true, bubbles: true, cancelable: true}))');

        $this->assertSame(self::FIXTURE, $this->tree($backend));
    }

    public function testEditedItemsAreSavedAsHtml(): void
    {
        $backend = $this->openListElement();

        $this->activate($backend, 1);
        $this->moveCaret($backend, true);

        $backend->page()->keyboard()->type('X');

        // The editor does not offer lists inside list items
        $this->assertSame('bold | link unlink | code', $backend->page()->evaluate('hugerte.activeEditor.options.get("toolbar")'));
        $this->assertArrayNotHasKey('lists', $backend->page()->evaluate('Object.fromEntries(Object.keys(hugerte.activeEditor.plugins).map(name => [name, true]))'));

        $backend->submitForm('Save');

        $this->assertSame(['Foo', '-<p>BarX</p>', '-Baz', 'Qux', 'Quux'], $this->tree($backend));

        $crawler = $this->requestPage();

        $this->assertSame('BarX', $crawler->filter('.content-list > ul > li:first-child > ul > li:first-child > p')->text());
    }

    public function testSubmittingWhileEditingKeepsTheChanges(): void
    {
        $backend = $this->openListElement();

        $this->activate($backend, 1);
        $this->moveCaret($backend, true);

        $backend->page()->keyboard()->type('X');

        // Submit the form while the item is still being edited
        $backend->waitForNavigation(static fn () => $backend->page()->evaluate('document.getElementById("save").form.requestSubmit(document.getElementById("save"))'));

        $this->assertSame(['Foo', '-<p>BarX</p>', '-Baz', 'Qux', 'Quux'], $this->tree($backend));
    }

    public function testCopiedItemsCanBeEdited(): void
    {
        $backend = $this->openListElement();

        $this->activate($backend, 0);
        $this->moveCaret($backend, true);

        $backend->page()->keyboard()->type('X');

        $this->rows($backend)->nth(0)->locator(':scope > [data-action^="contao--list-wizard#copy"]')->click();

        // The copy is focused and edited with its own editor
        $this->waitForEditor($backend, 3);
        $this->assertSame('FooX', trim($this->editor($backend, 3)->innerText()));

        $this->moveCaret($backend, true);

        $backend->page()->keyboard()->type('Y');
        $backend->submitForm('Save');

        $this->assertSame(['<p>FooX</p>', '-Bar', '-Baz', '<p>FooXY</p>', '-Bar', '-Baz', 'Qux', 'Quux'], $this->tree($backend));
    }

    public function testDraggingSortsItemsWithinTheirList(): void
    {
        $backend = $this->openListElement();

        $this->rows($backend)->nth(4)->locator(':scope > .drag-handle')->dragTo(
            $this->rows($backend)->nth(0)->locator(':scope > .drag-handle'),
            ['targetPosition' => ['x' => 8, 'y' => 2]],
        );

        $expected = ['Quux', 'Foo', '-Bar', '-Baz', 'Qux'];

        $this->assertSame($expected, $this->tree($backend));

        $backend->submitForm('Save');

        $this->assertSame($expected, $this->tree($backend));
    }

    #[DataProvider('dragNestingProvider')]
    public function testDraggingMovesItemsBetweenLevels(int $row, int $target, array $expected): void
    {
        $backend = $this->openListElement();

        $this->rows($backend)->nth($row)->locator(':scope > .drag-handle')->dragTo(
            $this->rows($backend)->nth($target)->locator(':scope > .drag-handle'),
            ['targetPosition' => ['x' => 8, 'y' => 2]],
        );

        $this->assertSame($expected, $this->tree($backend));

        $backend->submitForm('Save');

        $this->assertSame($expected, $this->tree($backend));
    }

    public static function dragNestingProvider(): iterable
    {
        yield 'into a nested list' => [3, 2, ['Foo', '-Bar', '-Qux', '-Baz', 'Quux']];
        yield 'out of a nested list' => [1, 4, ['Foo', '-Baz', 'Qux', 'Bar', 'Quux']];
        yield 'not into its own children' => [0, 1, self::FIXTURE];
    }

    public function testDraggingIntoAnEmptyNestedList(): void
    {
        $backend = $this->openListElement();
        $mouse = $backend->page()->mouse();

        $handle = $this->rows($backend)->nth(4)->locator(':scope > .drag-handle');
        $start = $handle->boundingBox();

        $mouse->move($start['x'] + $start['width'] / 2, $start['y'] + $start['height'] / 2);
        $mouse->down();
        $mouse->move($start['x'] + $start['width'] / 2 + 10, $start['y'] + $start['height'] / 2, ['steps' => 5]);

        $backend->page()->locator('.sortable-ghost')->waitFor();

        // Approach the empty list below "Qux" from its empty insert margin
        for ($step = 0; $step < 2; ++$step) {
            $end = $this->rows($backend)->nth(3)->locator(':scope > ul')->evaluate('el => { const r = el.getBoundingClientRect(); return {x: r.left - 2, y: r.top}; }');
            $mouse->move($end['x'] + $step, $end['y']);
            $backend->page()->evaluate('() => new Promise(resolve => setTimeout(resolve, 150))');
        }

        $mouse->up();

        $this->assertSame(['Foo', '-Bar', '-Baz', 'Qux', '-Quux'], $this->tree($backend));

        $backend->submitForm('Save');

        $this->assertSame(['Foo', '-Bar', '-Baz', 'Qux', '-Quux'], $this->tree($backend));
    }

    public function testDragHandlesSortItemsWithTheKeyboard(): void
    {
        $backend = $this->openListElement();

        // A nested item only moves within its list
        $this->rows($backend)->nth(1)->locator(':scope > .drag-handle')->press('ArrowDown');
        $this->assertSame(['Foo', '-Baz', '-Bar', 'Qux', 'Quux'], $this->tree($backend));

        // Moving the first item up wraps it to the end
        $this->rows($backend)->nth(0)->locator(':scope > .drag-handle')->press('ArrowUp');

        $expected = ['Qux', 'Quux', 'Foo', '-Baz', '-Bar'];

        $this->assertSame($expected, $this->tree($backend));

        $backend->submitForm('Save');

        $this->assertSame($expected, $this->tree($backend));
    }

    public function testImportReplacesTheItems(): void
    {
        $backend = $this->openListElement();
        $file = sys_get_temp_dir().'/list-wizard-'.bin2hex(random_bytes(4)).'.csv';
        file_put_contents($file, "Grault,Garply\nWaldo\n");

        try {
            $backend->waitForNavigation(static fn () => $backend->clickButton('a[href*="key=list"]'));
            $backend->page()->locator('input[type="file"]')->setInputFiles($file);
            $backend->select('separator', 'comma');
            $backend->waitForNavigation(static fn () => $backend->clickButton('#save'));
        } finally {
            unlink($file);
        }

        $backend->waitFor('#ctrl_'.self::NESTED);

        $this->assertSame(['Grault', 'Garply', 'Waldo'], $this->tree($backend));
    }

    public function testNestedItemsAreRenderedInTheFrontEnd(): void
    {
        $crawler = $this->requestPage();

        $this->assertSame(['Foo', 'Qux', 'Quux'], $crawler->filter('.content-list > ul > li')->each(static fn ($li) => trim($li->getNode(0)->firstChild->textContent)));
        $this->assertSame(['Bar', 'Baz'], $crawler->filter('.content-list > ul > li:first-child > ul > li')->each(static fn ($li) => trim($li->text())));
    }

    public function testEmptyItemsAreRenderedInTheFrontEnd(): void
    {
        $backend = $this->openListElement();

        $this->activate($backend, 4);
        $this->moveCaret($backend, true);

        $backend->page()->keyboard()->press('Enter');
        $backend->submitForm('Save');

        $this->assertSame([...self::FIXTURE, ''], $this->tree($backend));

        $crawler = $this->requestPage();

        $this->assertSame(['Foo', 'Qux', 'Quux', ''], $crawler->filter('.content-list > ul > li')->each(static fn ($li) => trim($li->getNode(0)->firstChild?->textContent ?? '')));
    }

    public function testBasicListWizardDoesNotNest(): void
    {
        $backend = $this->openListElement();
        $wizard = $this->wizard($backend, self::BASIC_LIST_WIZARD);
        $keyboard = $backend->page()->keyboard();

        $this->assertSame(0, $wizard->locator('.indent, .outdent')->count());
        $this->assertSame([''], $this->items($backend));

        // Enter adds an item, the arrow keys do not change the nesting
        $this->input($backend, 0, self::BASIC_LIST_WIZARD)->fill('First');

        $keyboard->press('Enter');
        $keyboard->type('Second');
        $keyboard->press('ArrowRight');

        $this->focus($backend, 1, 'start', self::BASIC_LIST_WIZARD);

        $keyboard->press('ArrowLeft');

        $this->assertSame(['First', 'Second'], $this->items($backend));
        $this->assertSame(0, $wizard->locator('ul ul li')->count());

        // ArrowUp at the start of the text moves to the previous item
        $keyboard->press('ArrowUp');
        $this->assertSame('First', $backend->page()->evaluate('document.activeElement.value'));

        $wizard->locator('li')->nth(0)->locator('[data-action^="contao--list-wizard#copy"]')->click();
        $this->assertSame(['First', 'First', 'Second'], $this->items($backend));

        $wizard->locator('li')->nth(2)->locator('[data-action^="contao--list-wizard#delete"]')->click();
        $this->assertSame(['First', 'First'], $this->items($backend));
    }

    protected static function createApplicationConfig(): ManagedEditionConfig
    {
        return self::createBackendApplicationConfig()->withSimulatedOrigins();
    }

    private function openListElement(): BackendBrowser
    {
        $backend = $this->login();
        $backend->visit(self::managedEdition()->database()->fixtures()->interpolate('/contao?do=article&table=tl_content&id={content_list}&act=edit'));
        $backend->waitFor('#ctrl_'.self::NESTED);

        // Show the list wizards when watching the tests
        $backend->page()->locator('#pal_list_legend')->evaluate('el => el.scrollIntoView({block: "start"})');

        return $backend;
    }

    private function requestPage(): Crawler
    {
        $browser = self::managedEdition()->createHttpBrowser(HttpBrowserOptions::create()->withSimulatedOrigin('https://localhost'));
        $browser->setServerParameter('HTTP_ACCEPT', 'text/html');

        $crawler = $browser->request('GET', '/microsite-home');

        $this->assertSame(200, $browser->getInternalResponse()->getStatusCode(), $browser->getInternalResponse()->getContent());

        return $crawler;
    }

    private function wizard(BackendBrowser $backend, string $field = self::NESTED): LocatorInterface
    {
        return $backend->page()->locator('#ctrl_'.$field);
    }

    /**
     * Returns all items in document order, including the nested ones.
     */
    private function rows(BackendBrowser $backend, string $field = self::NESTED): LocatorInterface
    {
        return $this->wizard($backend, $field)->locator('li[data-contao--list-wizard-target="row"]');
    }

    private function input(BackendBrowser $backend, int $row, string $field = self::NESTED): LocatorInterface
    {
        return $this->rows($backend, $field)->nth($row)->locator(':scope > input');
    }

    /**
     * Focuses an input of the basic list wizard and puts the caret at the start or end.
     */
    private function focus(BackendBrowser $backend, int $row, string $caret, string $field = self::BASIC_LIST_WIZARD): void
    {
        $this->input($backend, $row, $field)->evaluate('(el, end) => { el.focus(); el.setSelectionRange(end ? el.value.length : 0, end ? el.value.length : 0) }', 'end' === $caret);
    }

    private function editor(BackendBrowser $backend, int $row): LocatorInterface
    {
        return $this->rows($backend)->nth($row)->locator(':scope > .inline-rte');
    }

    private function activate(BackendBrowser $backend, int $row): void
    {
        $this->editor($backend, $row)->click();
        $this->waitForEditor($backend, $row);
    }

    private function waitForEditor(BackendBrowser $backend, int $row): void
    {
        $this->rows($backend)->nth($row)->locator(':scope > .inline-rte[contenteditable="true"]:focus')->waitFor();
    }

    /**
     * Moves the caret of the active inline editor to the start or end of its text.
     */
    private function moveCaret(BackendBrowser $backend, bool $end): void
    {
        $backend->page()->evaluate('end => { const editor = hugerte.activeEditor; editor.selection.select(editor.getBody(), true); editor.selection.collapse(!end) }', $end);
    }

    /**
     * Returns the items prefixed with a dash per level and makes sure the input names
     * match their position.
     */
    private function tree(BackendBrowser $backend): array
    {
        return $this->wizard($backend)->evaluate(
            <<<'JS'
                (root, field) => {
                    const items = [];

                    const walk = (list, prefix, depth) => {
                        [...list.querySelectorAll(':scope > li')].forEach((row, i) => {
                            const name = `${prefix}[${i}]`;
                            const input = row.querySelector(':scope > input');

                            items.push(input.name === `${name}[item]` ? '-'.repeat(depth) + input.value : `Unexpected name ${input.name}`);
                            walk(row.querySelector(':scope > ul'), `${name}[list]`, depth + 1);
                        });
                    };

                    walk(root, field, 0);

                    return items;
                }
                JS,
            self::NESTED,
        );
    }

    private function items(BackendBrowser $backend): array
    {
        return $this->wizard($backend, self::BASIC_LIST_WIZARD)->locator('input')->evaluateAll(
            '(inputs, field) => inputs.map(input => input.name === `${field}[]` ? input.value : `Unexpected name ${input.name}`)',
            self::BASIC_LIST_WIZARD,
        );
    }

    private function buttonStates(BackendBrowser $backend): array
    {
        return $this->rows($backend)->evaluateAll('rows => rows.map(row => [!row.querySelector(":scope > .indent").disabled, !row.querySelector(":scope > .outdent").disabled])');
    }
}
