<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Loader;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\AutoRefreshTemplateHierarchyListener;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AutoRefreshTemplateHierarchyListenerTest extends TestCase
{
    /**
     * @dataProvider provideRequestScenarios
     */
    public function testRefreshesHierarchyOnKernelRequest(RequestEvent $event, string $environment, bool $shouldRefresh): void
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($shouldRefresh ? $this->once() : $this->never())
            ->method('warmUp')
            ->with(true)
        ;

        $listener = new AutoRefreshTemplateHierarchyListener($loader, $environment);
        $listener($event);
    }

    public function provideRequestScenarios(): \Generator
    {
        $mainRequestEvent = $this->createMock(RequestEvent::class);
        $mainRequestEvent
            ->method('isMainRequest')
            ->willReturn(true)
        ;

        $subRequestEvent = $this->createMock(RequestEvent::class);
        $subRequestEvent
            ->method('isMainRequest')
            ->willReturn(false)
        ;

        yield 'dev env, main request' => [$mainRequestEvent, 'dev', true];
        yield 'dev env, sub request' => [$subRequestEvent, 'dev', false];
        yield 'prod env, main request' => [$mainRequestEvent, 'prod', false];
        yield 'prod env, sub request' => [$subRequestEvent, 'prod', false];
    }
}
