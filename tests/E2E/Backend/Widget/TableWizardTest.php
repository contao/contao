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
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\E2eTests\Backend\BackendTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Playwright\Locator\LocatorInterface;

class TableWizardTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    private const string RTE = 'tableitems';

    private const string BASIC_TABLE_WIZARD = 'table_wizard_basic';

    private const array FIXTURE = [
        ['Name', 'Value', 'Link'],
        ['Lorem', 'Ipsum', 'Contao'],
        ['Dolor', 'Sit', 'Amet'],
    ];

    public function testEditedCellsPersist(): void
    {
        $backend = $this->openTableElement();

        $this->edit($backend, 1, 1, 'Dolor');
        $backend->submitForm('Save');

        $this->assertSame('IpsumDolor', $this->grid($backend)[1][1]);
    }

    #[DataProvider('operationProvider')]
    public function testOperationsChangeRowsAndColumns(string $axis, string $operation, array $expected): void
    {
        $backend = $this->openTableElement();

        $this->runOperation($backend, $axis, 1, $operation);

        $this->assertSame($expected, $this->grid($backend));

        $backend->submitForm('Save');

        $this->assertSame($expected, $this->grid($backend));
    }

    public static function operationProvider(): iterable
    {
        [$head, $middle, $foot] = self::FIXTURE;
        $columns = static fn (\Closure $callback) => array_map($callback, self::FIXTURE);

        yield 'insert a row above' => ['row', 'insertBefore', [$head, ['', '', ''], $middle, $foot]];
        yield 'insert a row below' => ['row', 'insertAfter', [$head, $middle, ['', '', ''], $foot]];
        yield 'duplicate a row' => ['row', 'duplicate', [$head, $middle, $middle, $foot]];
        yield 'clear a row' => ['row', 'clear', [$head, ['', '', ''], $foot]];
        yield 'delete a row' => ['row', 'remove', [$head, $foot]];
        yield 'insert a column left' => ['column', 'insertBefore', $columns(static fn ($row) => [$row[0], '', $row[1], $row[2]])];
        yield 'insert a column right' => ['column', 'insertAfter', $columns(static fn ($row) => [$row[0], $row[1], '', $row[2]])];
        yield 'duplicate a column' => ['column', 'duplicate', $columns(static fn ($row) => [$row[0], $row[1], $row[1], $row[2]])];
        yield 'clear a column' => ['column', 'clear', $columns(static fn ($row) => [$row[0], '', $row[2]])];
        yield 'delete a column' => ['column', 'remove', $columns(static fn ($row) => [$row[0], $row[2]])];
    }

    public function testDeletingTheLastRowOrColumnClearsIt(): void
    {
        $backend = $this->openTableElement();

        $this->runOperation($backend, 'row', 0, 'remove');
        $this->runOperation($backend, 'row', 0, 'remove');
        $this->assertSame([self::FIXTURE[2]], $this->grid($backend));

        // The last row is kept but emptied
        $this->runOperation($backend, 'row', 0, 'remove');
        $this->assertSame([['', '', '']], $this->grid($backend));

        $this->runOperation($backend, 'column', 0, 'remove');
        $this->runOperation($backend, 'column', 0, 'remove');
        $this->runOperation($backend, 'column', 0, 'remove');
        $this->assertSame([['']], $this->grid($backend));
    }

    #[DataProvider('appearanceToggleProvider')]
    public function testMenuOffersAppearanceTogglesAtTheEdges(string $axis, int $index, array $expected): void
    {
        $backend = $this->openTableElement();
        $menu = $this->openMenu($backend, $axis, $index);

        $this->assertSame($expected, $menu->locator('[role="menuitemcheckbox"]')->evaluateAll('items => items.map(item => item.dataset.operation)'));
    }

    public static function appearanceToggleProvider(): iterable
    {
        yield 'first row' => ['row', 0, ['toggleHead']];
        yield 'middle row' => ['row', 1, []];
        yield 'last row' => ['row', 2, ['toggleFoot']];
        yield 'first column' => ['column', 0, ['toggleLeft']];
        yield 'last column' => ['column', 2, []];
    }

    public function testAppearanceTogglesUpdateTheCheckboxes(): void
    {
        $backend = $this->openTableElement();
        $checkbox = static fn (string $name) => $backend->page()->locator(\sprintf('input[type="checkbox"][name="%s"]', $name));

        $this->assertTrue($this->hasClass($backend, 'table-wizard-has-head'));

        $this->toggle($backend, 'row', 0, 'toggleHead', false);
        $this->toggle($backend, 'row', 2, 'toggleFoot', true);
        $this->toggle($backend, 'column', 0, 'toggleLeft', true);

        $this->assertFalse($checkbox('thead')->isChecked());
        $this->assertTrue($checkbox('tfoot')->isChecked());
        $this->assertTrue($checkbox('tleft')->isChecked());
        $this->assertFalse($this->hasClass($backend, 'table-wizard-has-head'));
        $this->assertTrue($this->hasClass($backend, 'table-wizard-has-foot'));
        $this->assertTrue($this->hasClass($backend, 'table-wizard-has-left'));

        // Changing a checkbox directly updates the table wizard as well
        $checkbox('tleft')->uncheck();

        $this->assertFalse($this->hasClass($backend, 'table-wizard-has-left'));

        $backend->submitForm('Save');

        $this->assertFalse($checkbox('thead')->isChecked());
        $this->assertTrue($checkbox('tfoot')->isChecked());
        $this->assertFalse($checkbox('tleft')->isChecked());
    }

    public function testMenuCanBeUsedWithTheKeyboard(): void
    {
        $backend = $this->openTableElement();
        $keyboard = $backend->page()->keyboard();
        $handle = $this->handle($backend, 'row', 1);
        $menu = $this->wizard($backend)->locator('.table-wizard-menu');

        $handle->press('Enter');
        $menu->waitFor();

        $this->assertSame('true', $handle->getAttribute('aria-expanded'));
        $this->assertSame('insertBefore', $this->focusedOperation($backend));

        // Arrow keys wrap around and Tab keeps the focus inside the menu
        $keyboard->press('ArrowUp');
        $this->assertSame('remove', $this->focusedOperation($backend));

        $keyboard->press('ArrowDown');
        $keyboard->press('Tab');
        $this->assertSame('insertBefore', $this->focusedOperation($backend));

        // Escape closes the menu and focuses the handle again
        $keyboard->press('Escape');
        $menu->waitFor(['state' => 'detached']);

        $this->assertSame('false', $handle->getAttribute('aria-expanded'));
        $this->assertTrue($handle->evaluate('el => el === document.activeElement'));
    }

    public function testMenuClosesWhenClickingTheHandleOrOutside(): void
    {
        $backend = $this->openTableElement();
        $handle = $this->handle($backend, 'column', 1);
        $menu = $this->wizard($backend)->locator('.table-wizard-menu');

        $handle->click();
        $menu->waitFor();
        $handle->click();
        $menu->waitFor(['state' => 'detached']);

        $handle->click();
        $menu->waitFor();
        $backend->page()->locator('input[name="summary"]')->click();
        $menu->waitFor(['state' => 'detached']);

        $this->assertSame('false', $handle->getAttribute('aria-expanded'));
    }

    public function testHandlesMoveRowsAndColumnsWithTheKeyboard(): void
    {
        $backend = $this->openTableElement();
        [$head, $middle, $foot] = self::FIXTURE;

        $this->handle($backend, 'row', 0)->press('ArrowDown');

        $this->assertSame([$middle, $head, $foot], $this->grid($backend));
        $this->assertTrue($this->handle($backend, 'row', 1)->evaluate('el => el === document.activeElement'));

        // The first column cannot be moved further to the left
        $this->handle($backend, 'column', 0)->press('ArrowLeft');
        $this->handle($backend, 'column', 0)->press('ArrowRight');

        $expected = array_map(static fn ($row) => [$row[1], $row[0], $row[2]], [$middle, $head, $foot]);

        $this->assertSame($expected, $this->grid($backend));
        $this->assertTrue($this->handle($backend, 'column', 1)->evaluate('el => el === document.activeElement'));

        $backend->submitForm('Save');

        $this->assertSame($expected, $this->grid($backend));
    }

    public function testDraggingHandlesSortsRowsAndColumns(): void
    {
        $backend = $this->openTableElement();
        [$head, $middle, $foot] = self::FIXTURE;

        $this->dragHandle($backend, 'row', 0, 2);
        $this->assertSame([$middle, $foot, $head], $this->grid($backend));

        $this->dragHandle($backend, 'column', 0, 2);
        $expected = array_map(static fn ($row) => [$row[1], $row[2], $row[0]], [$middle, $foot, $head]);
        $this->assertSame($expected, $this->grid($backend));

        $backend->submitForm('Save');

        $this->assertSame($expected, $this->grid($backend));
    }

    public function testHandlesHighlightTheirRowOrColumn(): void
    {
        $backend = $this->openTableElement();

        // Move the pointer to the empty part of the toolbar, so the page does not scroll
        // and the pointer does not cross another handle
        $outside = $this->wizard($backend)->locator('.table-wizard-toolbar');

        $this->handle($backend, 'row', 1)->hover();
        $this->assertSame([[1, 0], [1, 1], [1, 2]], $this->activeCells($backend));

        $outside->hover(['position' => ['x' => 300, 'y' => 12]]);
        $this->assertSame([], $this->activeCells($backend));

        $this->handle($backend, 'column', 2)->focus();
        $this->assertSame([[0, 2], [1, 2], [2, 2]], $this->activeCells($backend));

        // The highlight stays while the menu is open, even when hovering another handle
        $this->handle($backend, 'column', 2)->click();
        $this->handle($backend, 'column', 1)->hover();
        $this->assertSame([[0, 2], [1, 2], [2, 2]], $this->activeCells($backend));

        $outside->hover(['position' => ['x' => 300, 'y' => 12]]);
        $this->assertSame([[0, 2], [1, 2], [2, 2]], $this->activeCells($backend));

        $backend->page()->keyboard()->press('Escape');
        $this->handle($backend, 'column', 2)->blur();
        $this->assertSame([], $this->activeCells($backend));
    }

    public function testAddButtonsAppendCellsAndFocusThem(): void
    {
        $backend = $this->openTableElement();
        $wizard = $this->wizard($backend, self::BASIC_TABLE_WIZARD);

        $wizard->locator('.table-wizard-add-row')->click();
        $this->assertSame([[''], ['']], $this->grid($backend, self::BASIC_TABLE_WIZARD));
        $this->assertSame([1, 0], $this->focusedCell($backend, self::BASIC_TABLE_WIZARD));

        $wizard->locator('.table-wizard-add-column')->click();
        $this->assertSame([['', ''], ['', '']], $this->grid($backend, self::BASIC_TABLE_WIZARD));
        $this->assertSame([0, 1], $this->focusedCell($backend, self::BASIC_TABLE_WIZARD));

        $wizard->locator('.table-wizard-add-both')->click();
        $this->assertSame(array_fill(0, 3, ['', '', '']), $this->grid($backend, self::BASIC_TABLE_WIZARD));
        $this->assertSame([2, 2], $this->focusedCell($backend, self::BASIC_TABLE_WIZARD));
    }

    public function testDraggingTheAddButtonsAddsAndRemovesEmptyCells(): void
    {
        $backend = $this->openTableElement();
        $step = $this->wizard($backend, self::BASIC_TABLE_WIZARD)->locator('.table-wizard-cell')->first()->boundingBox();

        // Dragging back to the start removes the rows again and does not add a row on release
        $this->dragAddButton($backend, 'row', [[0, 2 * $step['height']], [0, 0]]);
        $this->assertSame([['']], $this->grid($backend, self::BASIC_TABLE_WIZARD));

        $this->dragAddButton($backend, 'row', [[0, 2 * $step['height']]]);
        $this->assertSame([[''], [''], ['']], $this->grid($backend, self::BASIC_TABLE_WIZARD));

        // Only empty trailing rows are removed
        $this->input($backend, self::BASIC_TABLE_WIZARD, 1, 0)->fill('Kept');
        $this->dragAddButton($backend, 'row', [[0, -2 * $step['height']]]);
        $this->assertSame([[''], ['Kept']], $this->grid($backend, self::BASIC_TABLE_WIZARD));

        $this->dragAddButton($backend, 'column', [[$step['width'], 0]]);
        $this->assertSame([['', ''], ['Kept', '']], $this->grid($backend, self::BASIC_TABLE_WIZARD));

        $this->dragAddButton($backend, 'both', [[$step['width'], $step['height']]]);
        $this->assertSame([['', '', ''], ['Kept', '', ''], ['', '', '']], $this->grid($backend, self::BASIC_TABLE_WIZARD));
    }

    public function testArrowKeysMoveBetweenCellsAtTheCaretBoundaries(): void
    {
        $backend = $this->openTableElement();
        $keyboard = $backend->page()->keyboard();

        $this->wizard($backend, self::BASIC_TABLE_WIZARD)->locator('.table-wizard-add-both')->click();
        $this->input($backend, self::BASIC_TABLE_WIZARD, 0, 0)->fill('ab');
        $this->input($backend, self::BASIC_TABLE_WIZARD, 0, 1)->fill('cd');
        $this->input($backend, self::BASIC_TABLE_WIZARD, 1, 0)->fill('ef');
        $this->input($backend, self::BASIC_TABLE_WIZARD, 1, 1)->fill('gh');

        // At the end of the text, the next cell is focused with the caret at the start
        $this->input($backend, self::BASIC_TABLE_WIZARD, 0, 0)->evaluate('el => { el.focus(); el.setSelectionRange(2, 2) }');

        // Modifier keys are left to the browser
        $keyboard->press('Shift+ArrowRight');
        $this->assertSame([0, 0, 2], $this->caret($backend));

        $keyboard->press('ArrowRight');
        $this->assertSame([0, 1, 0], $this->caret($backend));

        // Inside the text, the caret moves as usual
        $keyboard->press('ArrowRight');
        $this->assertSame([0, 1, 1], $this->caret($backend));

        $keyboard->press('ArrowLeft');
        $this->assertSame([0, 1, 0], $this->caret($backend));

        // Moving back puts the caret at the end of the previous cell
        $keyboard->press('ArrowLeft');
        $this->assertSame([0, 0, 2], $this->caret($backend));

        $keyboard->press('ArrowDown');
        $this->assertSame([1, 0, 0], $this->caret($backend));

        $keyboard->press('ArrowUp');
        $this->assertSame([0, 0, 2], $this->caret($backend));

        // A selection is collapsed instead of leaving the cell
        $keyboard->press('Shift+ArrowLeft');
        $keyboard->press('ArrowDown');
        $this->assertSame([0, 0, 2], $this->caret($backend));
    }

    public function testArrowKeysMoveBetweenRichTextCells(): void
    {
        $backend = $this->openTableElement();
        $keyboard = $backend->page()->keyboard();

        $this->activate($backend, 0, 0);
        $this->moveRteCaret($backend, true);

        // Modifier keys are left to the browser
        $keyboard->press('Shift+ArrowRight');
        $this->assertSame([0, 0], $this->focusedCell($backend, self::RTE));

        $this->moveRteCaret($backend, true);
        $keyboard->press('ArrowRight');
        $this->waitForEditor($backend, 0, 1);

        // Inside the text, the caret moves as usual
        $keyboard->press('ArrowRight');
        $this->assertSame([0, 1], $this->focusedCell($backend, self::RTE));

        $this->moveRteCaret($backend, true);
        $keyboard->press('ArrowDown');
        $this->waitForEditor($backend, 1, 1);

        $this->moveRteCaret($backend, false);
        $keyboard->press('ArrowUp');
        $this->waitForEditor($backend, 0, 1);

        $this->moveRteCaret($backend, false);
        $keyboard->press('ArrowLeft');
        $this->waitForEditor($backend, 0, 0);

        // Escape leaves the editor
        $keyboard->press('Escape');
        $backend->page()->locator('.inline-rte[contenteditable="true"]:focus')->waitFor(['state' => 'detached']);
    }

    public function testLinksDoNotOpenBeforeEditing(): void
    {
        $backend = $this->openTableElement();
        $url = $backend->page()->url();

        $this->cell($backend, 1, 2)->locator('.inline-rte a')->click();
        $this->waitForEditor($backend, 1, 2);

        $this->assertSame($url, $backend->page()->url());
    }

    public function testDuplicatedRichTextCellsCanBeEdited(): void
    {
        $backend = $this->openTableElement();
        $copy = $this->cell($backend, 2, 0)->locator('.inline-rte');

        $this->edit($backend, 1, 0, 'X');
        $this->runOperation($backend, 'row', 1, 'duplicate');

        // The copy shows the edited content but is not an editor yet
        $this->assertSame('LoremX', trim($copy->innerText()));
        $this->assertNull($copy->getAttribute('contenteditable'));

        $this->edit($backend, 2, 0, 'Y');
        $backend->submitForm('Save');

        $this->assertSame('LoremX', $this->grid($backend)[1][0]);
        $this->assertSame('LoremXY', $this->grid($backend)[2][0]);
    }

    public function testResizingACellSyncsItsRowAndColumn(): void
    {
        $backend = $this->openTableElement();
        $this->wizard($backend, self::BASIC_TABLE_WIZARD)->locator('.table-wizard-add-both')->click();

        $sizes = fn () => $this->wizard($backend, self::BASIC_TABLE_WIZARD)->locator('textarea')->evaluateAll('inputs => inputs.map(input => [input.offsetWidth, input.offsetHeight])');
        [$initial] = $sizes();

        // Drag the resize handle in the bottom right corner of the first cell
        $box = $this->input($backend, self::BASIC_TABLE_WIZARD, 0, 0)->boundingBox();
        $mouse = $backend->page()->mouse();
        $mouse->move($box['x'] + $box['width'] - 3, $box['y'] + $box['height'] - 3);
        $mouse->down();
        $mouse->move($box['x'] + $box['width'] + 60, $box['y'] + $box['height'] + 20, ['steps' => 5]);
        $mouse->up();

        $backend->page()->waitForFunction(
            <<<'JS'
                ([width, height]) => {
                    const [first, second, third] = document.querySelectorAll('textarea[name^="table_wizard_basic"]');

                    return first.offsetWidth > width && first.offsetHeight > height && third.offsetWidth === first.offsetWidth && second.offsetHeight === first.offsetHeight;
                }
                JS,
            $initial,
        );

        [[$width, $height]] = $sizes();

        $this->assertSame([[$width, $height], [$initial[0], $height], [$width, $initial[1]], $initial], $sizes());
    }

    public function testZoomButtonsResizeAllCellsAndRememberTheSize(): void
    {
        $backend = $this->openTableElement();
        $wizard = $this->wizard($backend, self::BASIC_TABLE_WIZARD);
        $expand = $wizard->locator('[data-action="contao--table-wizard#expandCellSizes"]');
        $wizard->locator('.table-wizard-add-both')->click();

        $initial = $this->cellSizes($wizard);

        // Basic and rich text cells have the same default size
        $this->assertSame($this->cellSizes($this->wizard($backend)), $initial);

        $expand->click();
        $expanded = $this->cellSizes($wizard);

        // All cells get the same, bigger size, which is remembered
        $this->assertCount(1, $expanded);
        $this->assertGreaterThan($initial[0][0], $expanded[0][0]);
        $this->assertGreaterThan($initial[0][1], $expanded[0][1]);
        $this->assertSame(\sprintf('%dpx|%dpx', ...$expanded[0]), $backend->page()->evaluate('localStorage.getItem("contao_table_wizard_cell_size")'));

        $wizard->locator('[data-action="contao--table-wizard#shrinkCellSizes"]')->click();
        $this->assertLessThan($expanded[0][0], $this->cellSizes($wizard)[0][0]);

        // The maximum size cannot be exceeded
        for ($i = 0; $i < 10; ++$i) {
            $expand->click();
        }

        $maximum = $this->cellSizes($wizard);
        $expand->click();
        $this->assertSame($maximum, $this->cellSizes($wizard));

        // The size is restored in all table wizards
        $backend->visit($backend->page()->url());
        $backend->waitFor('.table-wizard:has(#ctrl_'.self::BASIC_TABLE_WIZARD.')');

        $this->assertSame([\sprintf('%dpx|%dpx', ...$maximum[0])], array_values(array_unique(
            $backend->page()->locator('.table-wizard-input:not([hidden])')->evaluateAll('inputs => inputs.map(input => `${input.style.width}|${input.style.height}`)'),
        )));
    }

    public function testImportReplacesTheTableItems(): void
    {
        $backend = $this->openTableElement();
        $file = sys_get_temp_dir().'/table-wizard-'.bin2hex(random_bytes(4)).'.csv';
        file_put_contents($file, "Year,Revenue\n2025,100\n2026,120\n");

        try {
            $backend->waitForNavigation(fn () => $this->wizard($backend)->locator('a[href*="key=table"]')->click());
            $backend->page()->locator('input[type="file"]')->setInputFiles($file);
            $backend->select('separator', 'comma');
            $backend->waitForNavigation(static fn () => $backend->clickButton('#save'));
        } finally {
            unlink($file);
        }

        $backend->waitFor('.table-wizard:has(#ctrl_'.self::RTE.')');

        $this->assertSame([['Year', 'Revenue'], ['2025', '100'], ['2026', '120']], $this->grid($backend));
    }

    public function testSubmittingWhileEditingKeepsTheChanges(): void
    {
        $backend = $this->openTableElement();

        $this->edit($backend, 1, 1, 'Dolor');

        // Submit the form while the cell is still being edited
        $backend->waitForNavigation(static fn () => $backend->page()->evaluate('document.getElementById("save").form.requestSubmit(document.getElementById("save"))'));

        $this->assertSame('IpsumDolor', $this->grid($backend)[1][1]);
    }

    public function testAddedCellsCanBeEditedRightAway(): void
    {
        $backend = $this->openTableElement();

        // The first cell of the new row is focused and edited directly
        $this->wizard($backend)->locator('.table-wizard-add-row')->click();
        $this->waitForEditor($backend, 3, 0);
        $backend->page()->keyboard()->type('Added');
        $backend->submitForm('Save');

        $this->assertSame(['Added', '', ''], $this->grid($backend)[3]);
    }

    public function testCancelledDragStopsAddingRows(): void
    {
        $backend = $this->openTableElement();
        $button = $this->wizard($backend)->locator('.table-wizard-add-row');

        // Leave enough room below the button to drag within the viewport
        $button->evaluate('el => el.scrollIntoView({block: "start"})');

        $box = $button->boundingBox();
        $mouse = $backend->page()->mouse();

        $mouse->move($box['x'] + $box['width'] / 2, $box['y'] + $box['height'] / 2);
        $mouse->down();

        // The browser takes over the pointer and no pointerup follows
        $backend->page()->evaluate('window.dispatchEvent(new PointerEvent("pointercancel"))');
        $mouse->move($box['x'] + $box['width'] / 2, $box['y'] + 150, ['steps' => 10]);
        $mouse->up();

        $this->assertCount(3, $this->grid($backend));
    }

    public function testEditMultipleTogglesTheAppearanceCheckboxes(): void
    {
        $backend = $this->login();
        $fixtures = self::managedEdition()->database()->fixtures();
        $id = (string) $fixtures->value('content_table');
        $field = self::RTE.'_'.$id;

        $backend->visit($fixtures->interpolate('/contao?do=article&table=tl_content&id={article_microsite_home}&act=select'));
        $backend->page()->locator(\sprintf('input[name="IDS[]"][value="%s"]', $id))->check();
        $backend->submitAction('Edit');
        $backend->page()->locator('input[name="all_fields[]"][value="tableitems"]')->check();
        $backend->page()->locator('input[name="all_fields[]"][value="thead"]')->check();
        $backend->submitAction('Continue');

        $thead = $backend->page()->locator(\sprintf('input[type="checkbox"][name="thead_%s"]', $id));

        // The header row of the fixture is reflected in the table wizard
        $this->assertTrue($thead->isChecked());
        $this->assertTrue($this->hasClass($backend, 'table-wizard-has-head', $field));

        $this->toggle($backend, 'row', 0, 'toggleHead', false, $field);

        $this->assertFalse($thead->isChecked());
        $this->assertFalse($this->hasClass($backend, 'table-wizard-has-head', $field));

        $backend->submitForm('Save');

        $this->assertFalse($thead->isChecked());
    }

    private function openTableElement(): BackendBrowser
    {
        $backend = $this->login();
        $backend->visit(self::managedEdition()->database()->fixtures()->interpolate('/contao?do=article&table=tl_content&id={content_table}&act=edit'));
        $backend->waitFor('.table-wizard:has(#ctrl_'.self::RTE.')');

        // Show the table wizards when watching the tests
        $backend->page()->locator('#pal_table_legend')->evaluate('el => el.scrollIntoView({block: "start"})');

        return $backend;
    }

    private function wizard(BackendBrowser $backend, string $field = self::RTE): LocatorInterface
    {
        return $backend->page()->locator(\sprintf('.table-wizard:has(#ctrl_%s)', $field));
    }

    private function rows(BackendBrowser $backend, string $field = self::RTE): LocatorInterface
    {
        return $this->wizard($backend, $field)->locator('.table-wizard-body > .table-wizard-row');
    }

    private function cell(BackendBrowser $backend, int $row, int $column): LocatorInterface
    {
        return $this->rows($backend)->nth($row)->locator(':scope > .table-wizard-cell')->nth($column);
    }

    private function input(BackendBrowser $backend, string $field, int $row, int $column): LocatorInterface
    {
        return $backend->page()->locator(\sprintf('textarea[name="%s[%d][%d]"]', $field, $row, $column));
    }

    private function handle(BackendBrowser $backend, string $axis, int $index, string $field = self::RTE): LocatorInterface
    {
        if ('row' === $axis) {
            return $this->rows($backend, $field)->nth($index)->locator('.table-wizard-handle-row');
        }

        return $this->wizard($backend, $field)->locator('.table-wizard-handle-column')->nth($index);
    }

    private function openMenu(BackendBrowser $backend, string $axis, int $index, string $field = self::RTE): LocatorInterface
    {
        $this->handle($backend, $axis, $index, $field)->click();

        $menu = $this->wizard($backend, $field)->locator('.table-wizard-menu');
        $menu->waitFor();

        return $menu;
    }

    /**
     * Runs an operation from the row or column menu.
     */
    private function runOperation(BackendBrowser $backend, string $axis, int $index, string $operation): void
    {
        $this->openMenu($backend, $axis, $index)->locator(\sprintf('[data-operation="%s"]', $operation))->click();
    }

    /**
     * Toggles an appearance option, which keeps the menu open, and closes the menu.
     */
    private function toggle(BackendBrowser $backend, string $axis, int $index, string $operation, bool $checked, string $field = self::RTE): void
    {
        $item = $this->openMenu($backend, $axis, $index, $field)->locator(\sprintf('[data-operation="%s"]', $operation));
        $item->click();

        $this->assertSame($checked ? 'true' : 'false', $item->getAttribute('aria-checked'));

        $backend->page()->keyboard()->press('Escape');
        $this->wizard($backend, $field)->locator('.table-wizard-menu')->waitFor(['state' => 'detached']);
    }

    /**
     * Activates the inline editor of a cell and appends the text.
     */
    private function edit(BackendBrowser $backend, int $row, int $column, string $text): void
    {
        $this->activate($backend, $row, $column);
        $this->moveRteCaret($backend, true);
        $backend->page()->keyboard()->type($text);
    }

    private function activate(BackendBrowser $backend, int $row, int $column): void
    {
        $this->cell($backend, $row, $column)->locator('.inline-rte')->click();
        $this->waitForEditor($backend, $row, $column);
    }

    private function waitForEditor(BackendBrowser $backend, int $row, int $column): void
    {
        $this->cell($backend, $row, $column)->locator('.inline-rte[contenteditable="true"]:focus')->waitFor();
    }

    /**
     * Moves the caret of the active inline editor to the start or end of its text.
     */
    private function moveRteCaret(BackendBrowser $backend, bool $end): void
    {
        $backend->page()->evaluate('end => { const editor = hugerte.activeEditor; editor.selection.select(editor.getBody(), true); editor.selection.collapse(!end) }', $end);
    }

    /**
     * Drags a row or column handle onto the far half of another one.
     */
    private function dragHandle(BackendBrowser $backend, string $axis, int $from, int $to): void
    {
        $target = $this->handle($backend, $axis, $to);
        $box = $target->boundingBox();
        $position = 'row' === $axis ? ['x' => $box['width'] / 2, 'y' => $box['height'] - 2] : ['x' => $box['width'] - 2, 'y' => $box['height'] / 2];

        $this->handle($backend, $axis, $from)->dragTo($target, ['targetPosition' => $position]);
    }

    /**
     * Presses an add button of the basic table wizard and moves the pointer by the
     * given offsets before releasing it.
     */
    private function dragAddButton(BackendBrowser $backend, string $axis, array $offsets): void
    {
        $button = $this->wizard($backend, self::BASIC_TABLE_WIZARD)->locator('.table-wizard-add-'.$axis);

        // Leave enough room around the button to drag within the viewport
        $button->evaluate('el => el.scrollIntoView({block: "center", inline: "center"})');

        $box = $button->boundingBox();
        $x = $box['x'] + $box['width'] / 2;
        $y = $box['y'] + $box['height'] / 2;

        $mouse = $backend->page()->mouse();
        $mouse->move($x, $y);
        $mouse->down();

        foreach ($offsets as [$dx, $dy]) {
            $mouse->move($x + $dx, $y + $dy, ['steps' => 10]);
        }

        $mouse->up();
    }

    /**
     * Returns the cell values and makes sure the input names match their position.
     */
    private function grid(BackendBrowser $backend, string $field = self::RTE): array
    {
        $grid = $this->wizard($backend, $field)->evaluate(
            <<<'JS'
                (el, field) => [...el.querySelectorAll('.table-wizard-row')].map((row, i) =>
                    [...row.querySelectorAll(':scope > .table-wizard-cell > textarea')].map((textarea, j) =>
                        textarea.name === `${field}[${i}][${j}]` ? textarea.value : `Unexpected name ${textarea.name}`,
                    ),
                )
                JS,
            $field,
        );

        return array_map(static fn (array $row) => array_map(static fn (string $value) => trim(strip_tags($value)), $row), $grid);
    }

    /**
     * Returns the distinct sizes of the cell inputs.
     */
    private function cellSizes(LocatorInterface $wizard): array
    {
        $sizes = $wizard->locator('.table-wizard-input:not([hidden])')->evaluateAll('inputs => inputs.map(input => [input.offsetWidth, input.offsetHeight])');

        return array_values(array_unique($sizes, SORT_REGULAR));
    }

    private function activeCells(BackendBrowser $backend): array
    {
        return $this->wizard($backend)->evaluate(
            <<<'JS'
                el => [...el.querySelectorAll('.table-wizard-cell.active')].map(cell => [
                    [...el.querySelectorAll('.table-wizard-row')].indexOf(cell.parentElement),
                    [...cell.parentElement.querySelectorAll(':scope > .table-wizard-cell')].indexOf(cell),
                ])
                JS,
        );
    }

    private function focusedCell(BackendBrowser $backend, string $field): array|null
    {
        return $this->wizard($backend, $field)->evaluate(
            <<<'JS'
                el => {
                    const cell = document.activeElement?.closest('.table-wizard-cell');

                    if (!cell || !el.contains(cell)) {
                        return null;
                    }

                    return [
                        [...el.querySelectorAll('.table-wizard-row')].indexOf(cell.parentElement),
                        [...cell.parentElement.querySelectorAll(':scope > .table-wizard-cell')].indexOf(cell),
                    ];
                }
                JS,
        );
    }

    /**
     * Returns the focused cell of the basic table wizard and the caret position.
     */
    private function caret(BackendBrowser $backend): array
    {
        return [...$this->focusedCell($backend, self::BASIC_TABLE_WIZARD) ?? [-1, -1], $backend->page()->evaluate('document.activeElement.selectionStart')];
    }

    private function focusedOperation(BackendBrowser $backend): string|null
    {
        return $backend->page()->evaluate('document.activeElement?.dataset.operation ?? null');
    }

    private function hasClass(BackendBrowser $backend, string $class, string $field = self::RTE): bool
    {
        return $this->wizard($backend, $field)->evaluate('(el, name) => el.classList.contains(name)', $class);
    }
}
