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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AutoRefreshTemplateHierarchyListenerTest extends TestCase
{
    #[DataProvider('provideRequestScenarios')]
    public function testRefreshesHierarchyOnKernelRequest(bool $isMainRequest, string $environment, bool $shouldRefresh): void
    {
        $event = $this->createMock(RequestEvent::class);
        $event
            ->method('isMainRequest')
            ->willReturn($isMainRequest)
        ;

        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($shouldRefresh ? $this->once() : $this->never())
            ->method('warmUp')
            ->with(true)
        ;

        $listener = new AutoRefreshTemplateHierarchyListener($loader, $environment);
        $listener($event);
    }

    public static function provideRequestScenarios(): iterable
    {
        yield 'dev env, main request' => [true, 'dev', true];
        yield 'dev env, sub request' => [false, 'dev', false];
        yield 'prod env, main request' => [true, 'prod', false];
        yield 'prod env, sub request' => [false, 'prod', false];
    }
}
