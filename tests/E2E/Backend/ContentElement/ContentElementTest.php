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
use Playwright\Dialog\DialogInterface;

class ContentElementTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testDeletingContentElementReturnsToParentArticle(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        [$backend] = $this->openArticle($fixtures, 'article_string_mount_home');

        $this->createTextElement($backend);

        $contentElement = '[data-contao--operations-menu-record-id-value]:has-text("Lorem ipsum")';
        $this->assertSame(1, $backend->page()->locator($contentElement)->count());

        $backend->page()->events()->onDialog(static fn (DialogInterface $dialog) => $dialog->accept());
        $backend->clickButton($contentElement.' [data-contao--operations-menu-target="controller"]');
        $backend->waitFor('.operations-menu.show');
        $backend->waitForNavigation(static fn () => $backend->clickButton('.operations-menu.show button[title^="Delete content element"]'));

        $expectedUrl = $fixtures->interpolate(
            '/contao?do=article&id={article_string_mount_home}&table=tl_content',
        );

        $this->assertStringEndsWith($expectedUrl, $backend->page()->url());
        $this->assertSame(0, $backend->page()->locator($contentElement)->count());
    }
}
