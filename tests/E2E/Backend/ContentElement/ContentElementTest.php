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
        [$backend, $articleUrl] = $this->openArticle();
        $this->createTextElement($backend);

        $record = '[data-contao--operations-menu-record-id-value]:has-text("Lorem ipsum")';
        $this->assertSame(1, $backend->page()->locator($record)->count());

        $backend->page()->events()->onDialog(static fn (DialogInterface $dialog) => $dialog->accept());
        $backend->clickButton($record.' [data-contao--operations-menu-target="controller"]');
        $backend->waitFor('.operations-menu.show');
        $backend->waitForNavigation(static fn () => $backend->clickButton('.operations-menu.show button[title^="Delete content element"]'));

        parse_str((string) parse_url($backend->page()->url(), PHP_URL_QUERY), $query);
        parse_str((string) parse_url($articleUrl, PHP_URL_QUERY), $expectedQuery);

        $this->assertSame('/contao', parse_url($backend->page()->url(), PHP_URL_PATH));
        $this->assertSame('article', $query['do'] ?? null);
        $this->assertSame($expectedQuery['id'], $query['id'] ?? null, $backend->page()->url());
        $this->assertSame('tl_content', $query['table'] ?? null);
        $this->assertSame(0, $backend->page()->locator($record)->count());
    }
}
