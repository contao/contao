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

use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\E2eTests\Backend\BackendTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

class ElementGroupTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    #[DataProvider('clipboardOperationProvider')]
    public function testPasteElementGroupIntoItselfOrItsChildren(string $operation, bool $canPaste): void
    {
        [$backend, $articleUrl] = $this->openArticle();
        $record = '[data-contao--operations-menu-record-id-value]';

        // Create an element group with another element group inside
        $this->createElementGroup($backend);
        $backend->clickTitlePrefix('Edit the child elements');
        $this->createElementGroup($backend);

        // Go back to the outer element group and move or copy it
        $backend->visit($articleUrl);
        $this->clipboard($backend, $record, $operation);

        // Paste at the top always works, paste after the element group itself only
        // when copying
        $this->assertPasteButton($backend, '.tl_header', 'pasteafter', true);
        $this->assertPasteButton($backend, $record, 'pasteafter', $canPaste);

        // Inside the outer element group: paste at the top and paste into the inner
        // element group
        $backend->clickTitlePrefix('Edit the child elements');

        $this->assertPasteButton($backend, '.tl_header', 'pasteafter', $canPaste);
        $this->assertPasteButton($backend, $record, 'pasteinto', $canPaste);

        // Inside the inner element group: paste at the top
        $backend->clickTitlePrefix('Edit the child elements');

        $this->assertPasteButton($backend, '.tl_header', 'pasteafter', $canPaste);
    }

    /**
     * Moving an element into itself is a circular reference, copying is fine.
     */
    public static function clipboardOperationProvider(): iterable
    {
        yield 'Move' => ['Move', false];
        yield 'Copy' => ['Copy', true];
    }

    public function testCanPasteAfterSiblingInsideElementGroup(): void
    {
        [$backend] = $this->openArticle();

        // Create an element group with another element group and a text element inside
        $this->createElementGroup($backend);
        $backend->clickTitlePrefix('Edit the child elements');
        $this->createElementGroup($backend);
        $this->createTextElement($backend);

        // Move the inner element group
        $group = '[data-contao--operations-menu-record-id-value]:has-text("Element group")';
        $text = '[data-contao--operations-menu-record-id-value]:has-text("Lorem ipsum")';

        $this->clipboard($backend, $group, 'Move');

        // Pasting after the text element is allowed, pasting after or into the element
        // group itself is not
        $this->assertPasteButton($backend, $text, 'pasteafter', true);
        $this->assertPasteButton($backend, $group, 'pasteafter', false);
        $this->assertPasteButton($backend, $group, 'pasteinto', false);
    }
}
