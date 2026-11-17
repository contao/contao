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

use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;

class TurboDomreadyTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testFiresTheDomreadyEventIfAPageIsRendered(): void
    {
        $backend = $this->login();
        $backend->visit('/contao?do=user');

        $page = $backend->page();

        $page->evaluate(
            <<<'JS'
                () => {
                    window.domreadyCount = 0;
                    window.addEvent('domready', () => window.domreadyCount++);
                }
                JS,
        );

        $backend->waitForTurboNavigation(static fn () => $backend->clickLink('Pages'));

        $this->assertSame(2, $page->evaluate('() => window.domreadyCount'));
    }
}
