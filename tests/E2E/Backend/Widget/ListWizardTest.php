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

        // At the end of the text, ArrowRight indents the item
        $this->focus($backend, 3, 'end');
        $keyboard->press('ArrowRight');

        $this->assertSame(['Foo', '-Bar', '-Baz', '-Qux', 'Quux'], $this->tree($backend));
        $this->assertSame(['Qux', 3], $this->focused($backend));

        // At the start of the text, ArrowLeft outdents it again
        $this->focus($backend, 3, 'start');
        $keyboard->press('ArrowLeft');

        $this->assertSame(self::FIXTURE, $this->tree($backend));
        $this->assertSame(['Qux', 0], $this->focused($backend));

        // ArrowUp and ArrowDown move to the previous or next item across the levels
        $keyboard->press('ArrowUp');
        $this->assertSame('Baz', $this->focused($backend)[0]);

        $this->focus($backend, 2, 'end');
        $keyboard->press('ArrowDown');
        $this->assertSame('Qux', $this->focused($backend)[0]);

        // Inside the text, the caret moves as usual
        $this->focus($backend, 3, 'end');
        $keyboard->press('ArrowLeft');

        $this->assertSame(self::FIXTURE, $this->tree($backend));
        $this->assertSame(['Qux', 2], $this->focused($backend));
    }

    public function testEnterAddsAnItemInsteadOfSubmittingTheForm(): void
    {
        $backend = $this->openListElement();
        $url = $backend->page()->url();

        $this->focus($backend, 1, 'end');
        $backend->page()->keyboard()->press('Enter');
        $backend->page()->keyboard()->type('Corge');

        $this->assertSame($url, $backend->page()->url());
        $this->assertSame(['Foo', '-Bar', '-Corge', '-Baz', 'Qux', 'Quux'], $this->tree($backend));

        $backend->submitForm('Save');

        $this->assertSame(['Foo', '-Bar', '-Corge', '-Baz', 'Qux', 'Quux'], $this->tree($backend));
    }

    public function testModifierKeysAndSelectionsAreLeftToTheBrowser(): void
    {
        $backend = $this->openListElement();
        $keyboard = $backend->page()->keyboard();

        // Extending the selection does not indent or outdent
        $this->focus($backend, 3, 'end');
        $keyboard->press('Shift+ArrowRight');
        $this->focus($backend, 1, 'start');
        $keyboard->press('Shift+ArrowLeft');

        $this->assertSame(self::FIXTURE, $this->tree($backend));

        // A selection is collapsed instead of outdenting the item
        $this->input($backend, 1)->evaluate('el => { el.focus(); el.select() }');
        $keyboard->press('ArrowLeft');

        $this->assertSame(self::FIXTURE, $this->tree($backend));

        // Confirming an IME composition does not add an item
        $this->input($backend, 1)->evaluate('el => el.dispatchEvent(new KeyboardEvent("keydown", {key: "Enter", isComposing: true, bubbles: true, cancelable: true}))');

        $this->assertSame(self::FIXTURE, $this->tree($backend));
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

        $this->focus($backend, 4, 'end');
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
     * Focuses an item and puts the caret at the start or end of its text.
     */
    private function focus(BackendBrowser $backend, int $row, string $caret, string $field = self::NESTED): void
    {
        $this->input($backend, $row, $field)->evaluate('(el, end) => { el.focus(); el.setSelectionRange(end ? el.value.length : 0, end ? el.value.length : 0) }', 'end' === $caret);
    }

    /**
     * Returns the value of the focused input and the caret position.
     */
    private function focused(BackendBrowser $backend): array
    {
        return $backend->page()->evaluate('[document.activeElement?.value ?? null, document.activeElement?.selectionStart ?? null]');
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
