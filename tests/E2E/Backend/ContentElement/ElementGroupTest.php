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

class ElementGroupTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testCanPasteAfterSiblingInsideElementGroup(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        [$backend] = $this->openArticle($fixtures, 'article_main_home');

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
