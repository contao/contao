<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\CoreBundle\Routing\ResponseContext\CspParser;
use PHPUnit\Framework\TestCase;

class CspParserTest extends TestCase
{
    /**
     * @dataProvider cspDirectivesProvider
     */
    public function testParsesCspHeader(string $cspHeader): void
    {
        $csp = (new CspParser())->fromCspHeader($cspHeader)
            ->disableHttpsTransformOnHttpsConnections()
            ->disableOldBrowserSupport()
        ;

        $result = rtrim($csp->getHeaderArray(false)['Content-Security-Policy'], '; ');

        $this->assertSame($cspHeader, $result);
    }

    public function cspDirectivesProvider(): \Generator
    {
        yield ["script-src 'none'"];
        yield ["script-src 'self'"];
        yield ["script-src 'unsafe-eval'"];
        yield ["script-src 'unsafe-inline'"];
        yield ["style-src 'none'"];
        yield ["style-src 'self'"];
        yield ["style-src 'unsafe-inline'"];
        yield ["script-src 'self' example.com"];
        yield ["script-src 'self' example.com; style-src 'self'"];
        yield ["script-src 'self' example.com; style-src 'self' 'unsafe-inline'"];
        yield ["script-src 'self' example.com; style-src 'self' 'unsafe-inline'; upgrade-insecure-requests"];
        yield ["frame-ancestors 'none'; script-src 'self' example.com"];
        yield ["img-src 'self' data:; script-src 'self' example.com"];
        yield ["frame-ancestors 'self' https://example.org https://example.com https://store.example.com"];
        yield ["default-src 'self'; script-src https://example.com"];
        yield ["base-uri 'self'"];
        yield ["font-src https://example.com/"];
    }
}
