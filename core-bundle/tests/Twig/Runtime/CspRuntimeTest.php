<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\CspRuntime;
use ParagonIE\CSPBuilder\CSPBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

class CspRuntimeTest extends TestCase
{
    public function testRetrievesNonceFromCspBuilder(): void
    {
        $cspBuilder = $this->createMock(CSPBuilder::class);
        $cspBuilder
            ->expects($this->once())
            ->method('nonce')
            ->with('script-src')
        ;

        $responseContext = (new ResponseContext())->add($cspBuilder);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        $runtime = new CspRuntime($responseContextAccessor, new RequestStack());

        $runtime->getNonce('script-src');
    }

    public function testAddsCspSource(): void
    {
        $cspBuilder = $this->createMock(CSPBuilder::class);
        $cspBuilder
            ->expects($this->once())
            ->method('addSource')
            ->with('script-src', 'https://example.com/files/foo/foobar.js')
        ;

        $responseContext = (new ResponseContext())->add($cspBuilder);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        $runtime = new CspRuntime($responseContextAccessor, new RequestStack());

        $runtime->addSource('script-src', 'https://example.com/files/foo/foobar.js');
    }
}
