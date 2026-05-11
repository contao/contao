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
    public function testRefreshesHierarchyOnKernelRequest(bool $isMainRequest): void
    {
        $event = $this->createStub(RequestEvent::class);
        $event
            ->method('isMainRequest')
            ->willReturn($isMainRequest)
        ;

        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($isMainRequest ? $this->once() : $this->never())
            ->method('warmUp')
            ->with(true)
        ;

        $listener = new AutoRefreshTemplateHierarchyListener($loader);
        $listener($event);
    }

    public static function provideRequestScenarios(): iterable
    {
        yield 'main request' => [true];
        yield 'sub request' => [false];
    }
}
