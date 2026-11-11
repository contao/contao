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
use Playwright\Locator\LocatorInterface;

class RowWizardTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testMinPopulatesRows(): void
    {
        $backend = $this->openNewTextElement();

        // Three rows are rendered right away and none of them can be deleted
        $this->assertRows($backend, 'row_wizard_with_min', 3);
        $this->assertSame(3, $this->buttons($backend, 'row_wizard_with_min', 'delete', ':disabled')->count());

        // A fourth row enables deleting again
        $this->add($backend, 'row_wizard_with_min');

        $this->assertRows($backend, 'row_wizard_with_min', 4);
        $this->assertSame(0, $this->buttons($backend, 'row_wizard_with_min', 'delete', ':disabled')->count());
    }

    public function testMaxHidesAddRowAndDisablesCopy(): void
    {
        $backend = $this->openNewTextElement();

        // An empty row wizard only shows the add button
        $this->assertRows($backend, 'row_wizard_with_max', 0);

        $this->add($backend, 'row_wizard_with_max');
        $this->add($backend, 'row_wizard_with_max');
        $this->add($backend, 'row_wizard_with_max');

        // With three rows, neither adding nor copying is possible
        $this->assertRows($backend, 'row_wizard_with_max', 3);
        $this->assertTrue($this->ghost($backend, 'row_wizard_with_max')->isHidden());
        $this->assertSame(3, $this->buttons($backend, 'row_wizard_with_max', 'copy', ':disabled')->count());

        // Deleting a row brings both back
        $this->buttons($backend, 'row_wizard_with_max', 'delete')->first()->click();

        $this->assertRows($backend, 'row_wizard_with_max', 2);
        $this->assertTrue($this->ghost($backend, 'row_wizard_with_max')->isVisible());
        $this->assertSame(0, $this->buttons($backend, 'row_wizard_with_max', 'copy', ':disabled')->count());
    }

    public function testCopiedRowsPersistInOrder(): void
    {
        $backend = $this->openNewTextElement();

        $this->add($backend, 'row_wizard_basic');
        $this->input($backend, 'row_wizard_basic', 0, 'name')->fill('First');
        $this->input($backend, 'row_wizard_basic', 0, 'number')->fill('1');
        $this->input($backend, 'row_wizard_basic', 0, 'type')->selectOption('b');

        // The copy is inserted directly below and keeps all values
        $this->buttons($backend, 'row_wizard_basic', 'copy')->first()->click();
        $this->assertRows($backend, 'row_wizard_basic', 2);
        $this->assertSame('First', $this->input($backend, 'row_wizard_basic', 1, 'name')->inputValue());

        $this->input($backend, 'row_wizard_basic', 1, 'name')->fill('Second');

        $backend->submitForm('Save');

        $this->assertRows($backend, 'row_wizard_basic', 2);
        $this->assertSame('First', $this->input($backend, 'row_wizard_basic', 0, 'name')->inputValue());
        $this->assertSame('Second', $this->input($backend, 'row_wizard_basic', 1, 'name')->inputValue());
        $this->assertSame('1', $this->input($backend, 'row_wizard_basic', 1, 'number')->inputValue());
        $this->assertSame('b', $this->input($backend, 'row_wizard_basic', 1, 'type')->inputValue());
    }

    public function testDeletingTheLastRowStoresAnEmptyValue(): void
    {
        $backend = $this->openNewTextElement();

        $this->add($backend, 'row_wizard_basic');
        $this->input($backend, 'row_wizard_basic', 0, 'name')->fill('Only');

        $backend->submitForm('Save');

        $this->assertRows($backend, 'row_wizard_basic', 1);

        $this->buttons($backend, 'row_wizard_basic', 'delete')->first()->click();

        $backend->submitForm('Save');

        // No empty row is rendered for an empty value, only the add button (see #10073)
        $this->assertRows($backend, 'row_wizard_basic', 0);
        $this->assertTrue($this->ghost($backend, 'row_wizard_basic')->isVisible());
    }

    public function testMandatoryFieldOfAnEmptyRowWizardDoesNotBlockSaving(): void
    {
        $backend = $this->openNewRowWizardMandatoryElement();

        // Without a row, the browser must not validate the mandatory field
        $this->assertRows($backend, 'row_wizard_with_mandatory', 0);
        $this->assertTrue($this->isFormValid($backend));

        // Saving the untouched row wizard does not create a row either
        $backend->submitForm('Save');

        $this->assertRows($backend, 'row_wizard_with_mandatory', 0);

        $this->add($backend, 'row_wizard_with_mandatory');
        $this->assertFalse($this->isFormValid($backend));

        $this->input($backend, 'row_wizard_with_mandatory', 0, 'name')->fill('Only');

        $backend->submitForm('Save');

        $this->assertRows($backend, 'row_wizard_with_mandatory', 1);

        // The same applies after deleting the last row, which stores an empty value
        $this->buttons($backend, 'row_wizard_with_mandatory', 'delete')->first()->click();
        $this->assertTrue($this->isFormValid($backend));

        $backend->submitForm('Save');

        $this->assertRows($backend, 'row_wizard_with_mandatory', 0);
    }

    public function testCopyingARowKeepsTheRowsOfANestedRowWizard(): void
    {
        $backend = $this->openNewTextElement();

        $this->add($backend, 'row_wizard_nested');

        $this->input($backend, 'row_wizard_nested', 0, 'title')->fill('Outer');

        // The nested row wizard starts empty as well
        $this->assertSame(0, $this->nestedRows($backend, 0)->count());

        $this->addNested($backend, 0);
        $this->nestedInput($backend, 0, 0)->fill('Inner');

        // Copying the outer row reconnects the nested row wizards
        $this->copyOuterRow($backend, 0);
        $this->assertRows($backend, 'row_wizard_nested', 2);

        $this->assertSame(1, $this->nestedRows($backend, 0)->count());
        $this->assertSame(1, $this->nestedRows($backend, 1)->count());
        $this->assertSame('Inner', $this->nestedInput($backend, 1, 0)->inputValue());

        $backend->submitForm('Save');

        $this->assertRows($backend, 'row_wizard_nested', 2);
        $this->assertSame('Inner', $this->nestedInput($backend, 0, 0)->inputValue());
        $this->assertSame('Inner', $this->nestedInput($backend, 1, 0)->inputValue());
    }

    public function testNestedRowsAddedAfterCopyingBelongToTheirRow(): void
    {
        $backend = $this->openNewTextElement();

        $this->add($backend, 'row_wizard_nested');

        $this->input($backend, 'row_wizard_nested', 0, 'title')->fill('Outer');

        // Both nested row wizards are still empty after copying the outer row
        $this->copyOuterRow($backend, 0);
        $this->assertRows($backend, 'row_wizard_nested', 2);

        $this->assertSame(0, $this->nestedRows($backend, 0)->count());
        $this->assertSame(0, $this->nestedRows($backend, 1)->count());

        // The nested rows are named after the outer row they have been added to
        $this->addNested($backend, 0);
        $this->nestedInput($backend, 0, 0)->fill('First');

        $this->addNested($backend, 1);
        $this->nestedInput($backend, 1, 0)->fill('Second');

        $this->addNested($backend, 1);
        $this->nestedInput($backend, 1, 1)->fill('Third');

        $backend->submitForm('Save');

        $this->assertRows($backend, 'row_wizard_nested', 2);
        $this->assertSame(1, $this->nestedRows($backend, 0)->count());
        $this->assertSame(2, $this->nestedRows($backend, 1)->count());
        $this->assertSame('First', $this->nestedInput($backend, 0, 0)->inputValue());
        $this->assertSame('Second', $this->nestedInput($backend, 1, 0)->inputValue());
        $this->assertSame('Third', $this->nestedInput($backend, 1, 1)->inputValue());
    }

    public function testMovingARowKeepsTheRowsOfANestedRowWizard(): void
    {
        $backend = $this->openNewTextElement();

        $this->add($backend, 'row_wizard_nested');
        $this->add($backend, 'row_wizard_nested');

        $this->assertRows($backend, 'row_wizard_nested', 2);

        $this->input($backend, 'row_wizard_nested', 0, 'title')->fill('First');
        $this->input($backend, 'row_wizard_nested', 1, 'title')->fill('Second');

        $this->addNested($backend, 0);
        $this->nestedInput($backend, 0, 0)->fill('Inner');

        // Moving the first row below the second one reconnects its nested row wizard
        $this->outerRow($backend, 0, '> td:first-child > .drag-handle')->focus();

        $backend->page()->keyboard()->press('ArrowDown');

        $this->assertSame('First', $this->input($backend, 'row_wizard_nested', 1, 'title')->inputValue());
        $this->assertSame(1, $this->nestedRows($backend, 1)->count());
        $this->assertSame('Inner', $this->nestedInput($backend, 1, 0)->inputValue());

        $backend->submitForm('Save');

        $this->assertSame('Second', $this->input($backend, 'row_wizard_nested', 0, 'title')->inputValue());
        $this->assertSame('First', $this->input($backend, 'row_wizard_nested', 1, 'title')->inputValue());
        $this->assertSame('Inner', $this->nestedInput($backend, 1, 0)->inputValue());
    }

    public function testActionsAndSortableOptionsControlTheButtons(): void
    {
        $backend = $this->openNewTextElement();

        // The first row of an empty row wizard is hidden until a row is added
        $this->add($backend, 'row_wizard_basic');
        $this->add($backend, 'row_wizard_with_limited_actions');

        // All three actions and the drag handle
        $this->assertSame(1, $this->buttons($backend, 'row_wizard_basic', 'copy')->count());
        $this->assertSame(1, $this->buttons($backend, 'row_wizard_basic', 'delete')->count());
        $this->assertSame(1, $backend->page()->locator('#ctrl_row_wizard_basic input.mw_enable')->count());
        $this->assertSame(1, $backend->page()->locator('#ctrl_row_wizard_basic .drag-handle')->count());

        // Only copy, the unknown "edit" action is ignored, no drag handle
        $this->assertSame(1, $this->buttons($backend, 'row_wizard_with_limited_actions', 'copy')->count());
        $this->assertSame(0, $this->buttons($backend, 'row_wizard_with_limited_actions', 'delete')->count());
        $this->assertSame(0, $backend->page()->locator('#ctrl_row_wizard_with_limited_actions input.mw_enable')->count());
        $this->assertSame(0, $backend->page()->locator('#ctrl_row_wizard_with_limited_actions .drag-handle')->count());
    }

    public function testEnableStateIsSavedPerRow(): void
    {
        $backend = $this->openNewTextElement();

        $this->add($backend, 'row_wizard_basic');
        $this->add($backend, 'row_wizard_basic');
        $this->input($backend, 'row_wizard_basic', 0, 'name')->fill('A');
        $this->input($backend, 'row_wizard_basic', 1, 'name')->fill('B');

        // Click the label like a user does, the checkbox itself is covered by it
        $backend->page()->locator('#ctrl_row_wizard_basic > tbody > tr:nth-child(2) label.mw_enable')->click();

        $this->assertFalse($this->input($backend, 'row_wizard_basic', 0, 'enable')->isChecked());
        $this->assertTrue($this->input($backend, 'row_wizard_basic', 1, 'enable')->isChecked());

        $backend->submitForm('Save');

        $this->assertFalse($this->input($backend, 'row_wizard_basic', 0, 'enable')->isChecked());
        $this->assertTrue($this->input($backend, 'row_wizard_basic', 1, 'enable')->isChecked());
    }

    public function testInvalidRowValueShowsAnError(): void
    {
        $backend = $this->openNewTextElement();

        $this->add($backend, 'row_wizard_basic');
        $this->input($backend, 'row_wizard_basic', 0, 'name')->fill('Keep');
        $this->input($backend, 'row_wizard_basic', 0, 'number')->fill('abc');

        // An invalid form is rendered again without a Turbo visit, so wait for the error
        // instead of the navigation
        $backend->page()->getByRole('button', ['name' => 'Save', 'exact' => true])->click();
        $backend->waitFor('#ctrl_row_wizard_basic > tbody .tl_error');

        // The nested widget shows its error and the other values are kept
        $this->assertSame('Keep', $this->input($backend, 'row_wizard_basic', 0, 'name')->inputValue());
    }

    /**
     * Creates a text element and stays in its edit form.
     */
    private function openNewTextElement(): BackendBrowser
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        [$backend] = $this->openArticle($fixtures, 'article_main_home');

        $backend->submitNew();
        $backend->submitAction('Paste at the top');
        $backend->waitFor('textarea[name="text"]');
        $backend->fillRichText('text', 'Lorem ipsum');

        return $backend;
    }

    private function openNewRowWizardMandatoryElement(): BackendBrowser
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        [$backend] = $this->openArticle($fixtures, 'article_main_home');

        $backend->submitNew();
        $backend->submitAction('Paste at the top');

        // Changing the type auto-submits the form so we wait until the text palette is gone
        $backend->select('type', 'row_wizard_mandatory');
        $backend->page()->locator('textarea[name="text"]')->waitFor(['state' => 'detached']);

        return $backend;
    }

    private function add(BackendBrowser $backend, string $field): void
    {
        $backend->page()->locator(\sprintf('#ctrl_%s > tfoot .row-wizard-add', $field))->click();
    }

    private function ghost(BackendBrowser $backend, string $field): LocatorInterface
    {
        return $backend->page()->locator(\sprintf('#ctrl_%s .row-wizard-ghost', $field));
    }

    /**
     * Returns whether the browser would submit the edit form.
     */
    private function isFormValid(BackendBrowser $backend): bool
    {
        return $backend->page()->locator('#ctrl_row_wizard_with_mandatory')->evaluate('el => el.closest("form").checkValidity()');
    }

    /**
     * Only looks at the body rows, because the ghost row in the footer contains
     * disabled copies of the inputs with the same names.
     */
    private function input(BackendBrowser $backend, string $field, int $row, string $key): LocatorInterface
    {
        return $backend->page()->locator(\sprintf('#ctrl_%1$s > tbody [name="%1$s[%2$d][%3$s]"]', $field, $row, $key));
    }

    /**
     * Returns the copy or delete buttons of the visible rows.
     */
    private function buttons(BackendBrowser $backend, string $field, string $action, string $state = ''): LocatorInterface
    {
        return $backend->page()->locator(\sprintf('#ctrl_%s > tbody > tr:visible button[data-contao--row-wizard-target="%s"]%s', $field, $action, $state));
    }

    /**
     * Waits for the given number of visible rows and asserts that there are not more.
     */
    private function assertRows(BackendBrowser $backend, string $field, int $count): void
    {
        $rows = $backend->page()->locator(\sprintf('#ctrl_%s > tbody > tr[data-contao--row-wizard-target="row"]:visible', $field));

        if ($count > 0) {
            $rows->nth($count - 1)->waitFor(['state' => 'visible']);
        }

        $this->assertSame($count, $rows->count());
    }

    /**
     * Returns an element inside a row of the "row_wizard_nested" field.
     */
    private function outerRow(BackendBrowser $backend, int $row, string $selector): LocatorInterface
    {
        return $backend->page()->locator(\sprintf('#ctrl_row_wizard_nested > tbody > tr:nth-child(%d) %s', $row + 1, $selector));
    }

    private function copyOuterRow(BackendBrowser $backend, int $row): void
    {
        $this->outerRow($backend, $row, '> td.tl_right > button[data-contao--row-wizard-target="copy"]')->click();
    }

    private function addNested(BackendBrowser $backend, int $row): void
    {
        $this->outerRow($backend, $row, 'table.row_wizard .row-wizard-add')->click();
    }

    /**
     * Returns the visible rows of the nested row wizard in the given row.
     */
    private function nestedRows(BackendBrowser $backend, int $row): LocatorInterface
    {
        return $this->outerRow($backend, $row, 'table.row_wizard > tbody > tr:visible');
    }

    private function nestedInput(BackendBrowser $backend, int $row, int $nestedRow): LocatorInterface
    {
        return $backend->page()->locator(\sprintf('#ctrl_row_wizard_nested > tbody table.row_wizard > tbody [name="row_wizard_nested[%d][items][%d][name]"]:visible', $row, $nestedRow));
    }
}
