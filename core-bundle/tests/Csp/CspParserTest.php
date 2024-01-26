<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Csp;

use Contao\CoreBundle\Csp\CspParser;
use Nelmio\SecurityBundle\ContentSecurityPolicy\PolicyManager;
use PHPUnit\Framework\TestCase;

class CspParserTest extends TestCase
{
    /**
     * @dataProvider directivesProvider
     */
    public function testParsesCspHeader(string $header, array $result): void
    {
        $cspParser = new CspParser(new PolicyManager());
        $directiveSet = $cspParser->parseHeader($header);

        foreach ($result as $name => $value) {
            $this->assertSame($value, $directiveSet->getDirective($name));
        }
    }

    public static function directivesProvider(): \Generator
    {
        yield ["default-src self; script-src 'none'; style-src unsafe-inline", ['default-src' => "'self'", 'script-src' => "'none'", 'style-src' => "'unsafe-inline'"]];
        yield ["script-src 'self' example.com", ['script-src' => "'self' example.com"]];
        yield ["style-src 'self' 'unsafe-inline'; upgrade-insecure-requests", ['style-src' => "'self' 'unsafe-inline'", 'upgrade-insecure-requests' => true]];
        yield ["frame-ancestors 'none'; script-src 'self' example.com", ['frame-ancestors' => "'none'", 'script-src' => "'self' example.com"]];
        yield ["img-src 'self' data:; script-src 'self' example.com", ['img-src' => "'self' data:", 'script-src' => "'self' example.com"]];
        yield ["frame-ancestors 'self' https://example.com https://store.example.com", ['frame-ancestors' => "'self' https://example.com https://store.example.com"]];
        yield ["base-uri 'self'; report-uri https://endpoint.com", ['base-uri' => "'self'", 'report-uri' => 'https://endpoint.com']];
        yield ['font-src https://example.com/', ['font-src' => 'https://example.com/']];
        yield ['script-src unsafe-hashed-attributes', ['script-src' => 'unsafe-hashed-attributes']];
        yield ['plugin-types application/x-java-applet', ['plugin-types' => 'application/x-java-applet']];
        yield ["form-action 'none'; worker-src https://example.com/", ['form-action' => "'none'", 'worker-src' => 'https://example.com/']];
    }
}
