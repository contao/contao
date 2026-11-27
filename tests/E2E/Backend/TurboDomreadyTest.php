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

    public function testDoesNotFireTheDomreadyEventAgainIfAFrameIsRendered(): void
    {
        $backend = $this->login();
        $backend->visit('/contao?do=user');

        $page = $backend->page();

        // MooTools calls the listener right away, because the DOM is ready
        $page->evaluate(
            <<<'JS'
                () => {
                    window.domreadyCount = 0;
                    window.addEvent('domready', () => window.domreadyCount++);
                    document.documentElement.addEventListener('turbo:frame-render', () => window.frameRendered = true);
                    document.getElementById('tl_content_filter').requestSubmit();
                }
                JS,
        );

        $page->waitForFunction('() => window.frameRendered === true');

        $this->assertSame(1, $page->evaluate('() => window.domreadyCount'));
    }

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
