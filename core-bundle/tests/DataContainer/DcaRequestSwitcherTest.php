<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\CoreBundle\DataContainer\DcaRequestSwitcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DcaRequestSwitcherTest extends TestCase
{
    public function testRunWithRequest(): void
    {
        $dcaLoader = $this->mockAdapter(['switchToCurrentRequest']);
        $dcaLoader
            ->expects($this->exactly(2))
            ->method('switchToCurrentRequest')
        ;

        $framework = $this->mockContaoFramework([DcaLoader::class => $dcaLoader]);

        $requestStack = new RequestStack();

        $switcher = new DcaRequestSwitcher($framework, $requestStack);
        $request = new Request();

        $callback = function () use ($request, $requestStack, $switcher): string {
            $this->assertSame($request, $requestStack->getCurrentRequest());

            // Nested calls with the same request should not switch twice
            $switcher->runWithRequest(
                $request,
                function () use ($request, $requestStack): void {
                    $this->assertSame($request, $requestStack->getCurrentRequest());
                    $this->assertNull($requestStack->getParentRequest());
                },
            );

            return 'return from closure';
        };

        $this->assertNull($requestStack->getCurrentRequest());
        $this->assertSame('return from closure', $switcher->runWithRequest($request, $callback));
        $this->assertNull($requestStack->getCurrentRequest());
    }

    public function testRunWithStringRequest(): void
    {
        $dcaLoader = $this->mockAdapter(['switchToCurrentRequest']);
        $dcaLoader
            ->expects($this->exactly(2))
            ->method('switchToCurrentRequest')
        ;

        $framework = $this->mockContaoFramework([DcaLoader::class => $dcaLoader]);

        $requestStack = new RequestStack();

        $switcher = new DcaRequestSwitcher($framework, $requestStack);

        $callback = function () use ($requestStack): string {
            $this->assertSame('https://localhost/foo?bar=baz', $requestStack->getCurrentRequest()->getUri());

            return 'return from closure';
        };

        $this->assertNull($requestStack->getCurrentRequest());
        $this->assertSame('return from closure', $switcher->runWithRequest('https://localhost/foo?bar=baz', $callback));
        $this->assertNull($requestStack->getCurrentRequest());
    }
}
