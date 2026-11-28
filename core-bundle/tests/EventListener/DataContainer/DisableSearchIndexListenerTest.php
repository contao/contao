<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\DisableSearchIndexListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class DisableSearchIndexListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_PURGE']);

        parent::tearDown();
    }

    public function testRemovesSearchDcaWithoutChangingPurgeJobs(): void
    {
        /** @phpstan-var array $GLOBALS */
        $GLOBALS['TL_DCA'] = [
            'tl_search' => [],
            'tl_search_index' => [],
            'tl_search_term' => [],
            'tl_page' => [],
        ];

        $purge = $GLOBALS['TL_PURGE']['tables'] = ['index' => [], 'log' => []];

        $listener = new DisableSearchIndexListener($this->mockScopeMatcher());
        $listener->onLoadDataContainer();

        $this->assertSame(['tl_page' => []], $GLOBALS['TL_DCA']);
        $this->assertSame($purge, $GLOBALS['TL_PURGE']['tables']);
    }

    /**
     * @dataProvider scopeProvider
     */
    public function testRemovesIndexPurgeJobOnlyForBackendRequests(string|null $scope): void
    {
        /** @phpstan-var array $GLOBALS */
        $GLOBALS['TL_PURGE']['tables'] = ['index' => [], 'log' => []];
        $dca = $GLOBALS['TL_DCA']['tl_search'] = [];

        $request = new Request();
        $request->attributes->set('_scope', $scope);

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener = new DisableSearchIndexListener($this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertSame('backend' === $scope ? ['log' => []] : ['index' => [], 'log' => []], $GLOBALS['TL_PURGE']['tables']);
        $this->assertSame($dca, $GLOBALS['TL_DCA']['tl_search']);
    }

    public static function scopeProvider(): iterable
    {
        yield 'backend' => ['backend'];
        yield 'frontend' => ['frontend'];
        yield 'no scope' => [null];
    }
}
