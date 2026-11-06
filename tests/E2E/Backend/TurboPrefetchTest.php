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
use Playwright\Network\RouteInterface;
use Playwright\Page\PageInterface;

class TurboPrefetchTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    /**
     * @see https://github.com/contao/contao/issues/10424
     */
    public function testKeepsTheAdoptedPrefetchRequestWhenTheLinkIsHoveredAgain(): void
    {
        $backend = $this->login();
        $backend->visit('/contao');

        $page = $backend->page();
        $link = $page->locator('a.navigation:text-is("Articles")');

        $url = $backend->browser()->uri($link->getAttribute('href'));

        $release = $this->holdResponses($page, $url);

        // Hovering starts the prefetch request, clicking the link adopts it for the visit
        $link->hover();
        $page->waitForRequest($url);
        $link->click();

        // Leaving and re-entering the link prefetches the same URL again
        $page->mouse()->move(0, 0);
        $link->hover();
        $page->waitForRequest($url);

        // The visit must still finish once the responses arrive
        $backend->waitForTurboNavigation($release);

        $this->assertStringStartsWith('Articles |', $page->title());
    }

    /**
     * @see https://github.com/hotwired/turbo/issues/1479
     */
    public function testCancelsTheAdoptedPrefetchRequestWhenAnotherLinkIsClicked(): void
    {
        $backend = $this->login();
        $backend->visit('/contao');

        $page = $backend->page();
        $link = $page->locator('a.navigation:text-is("Articles")');

        $url = $backend->browser()->uri($link->getAttribute('href'));

        $release = $this->holdResponses($page, $url);

        // Remember the request the visit adopts
        $page->evaluate(
            <<<'JS'
                () => document.addEventListener('turbo:before-fetch-request', (event) => {
                    window.adoptedRequest = event.detail.fetchRequest ?? window.adoptedRequest;
                })
                JS,
        );

        $link->hover();
        $page->waitForRequest($url);
        $link->click();

        // Visiting another page while the first visit is still waiting for its response
        $backend->waitForTurboNavigation(static fn () => $backend->clickLink('Pages'));

        $this->assertTrue($page->evaluate('() => window.adoptedRequest.abortSignal.aborted'), 'The request of the superseded visit was not cancelled');

        // The late response must not render over the current page
        $release();

        $this->assertStringStartsWith('Pages |', $page->title());
    }

    /**
     * Holds back the responses to the URL and returns the callback that releases them.
     *
     * @return callable(): void
     */
    private function holdResponses(PageInterface $page, string $url): callable
    {
        $routes = [];

        $page->route(
            $url,
            static function (RouteInterface $route) use (&$routes): void {
                $routes[] = $route;
            },
        );

        return static function () use (&$routes): void {
            foreach ($routes as $route) {
                $route->continue();
            }
        };
    }
}
