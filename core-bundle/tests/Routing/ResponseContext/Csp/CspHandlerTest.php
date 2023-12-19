<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext\Csp;

use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Nelmio\SecurityBundle\ContentSecurityPolicy\DirectiveSet;
use Nelmio\SecurityBundle\ContentSecurityPolicy\PolicyManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CspHandlerTest extends TestCase
{
    public function testGeneratesNonce(): void
    {
        $cspHandler = $this->getCspHandler();
        $nonce = $cspHandler->getNonce('script-src');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertNotNull($nonce);
        $this->assertStringContainsString('nonce-'.$nonce, $response->headers->get('Content-Security-Policy'));
    }

    public function testDoesNotGenerateNonceIfNoDirectiveSet(): void
    {
        $cspHandler = $this->getCspHandler(['style-src' => "'self'"]);
        $nonce = $cspHandler->getNonce('script-src');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertNull($nonce);
        $this->assertStringNotContainsString('script-src', $response->headers->get('Content-Security-Policy'));
    }

    public function testGeneratesHash(): void
    {
        $cspHandler = $this->getCspHandler();
        $cspHandler->addHash('script-src', 'doSomething();');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertStringContainsString("script-src 'self' 'sha384-", $response->headers->get('Content-Security-Policy'));
    }

    public function testDoesNotGenerateHashIfNoDirectiveSet(): void
    {
        $cspHandler = $this->getCspHandler(['style-src' => "'self'"]);
        $cspHandler->addHash('script-src', 'doSomething();');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertStringNotContainsString('script-src', $response->headers->get('Content-Security-Policy'));
    }

    public function testAddsSource(): void
    {
        $cspHandler = $this->getCspHandler(['frame-src' => "'self'"]);
        $cspHandler->addSource('frame-src', 'www.youtube.com');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertStringContainsString("frame-src 'self' www.youtube.com", $response->headers->get('Content-Security-Policy'));
    }

    public function testChecksIfDiretiveOrFallbackIsSet(): void
    {
        $cspHandler = $this->getCspHandler(['default-src' => "'self'"]);
        $this->assertTrue($cspHandler->isDirectiveSet('script-src'));

        $cspHandler = $this->getCspHandler(['default-src' => "'self'"]);
        $this->assertFalse($cspHandler->isDirectiveSet('script-src', false));
    }

    public function testAppliesHeaders(): void
    {
        $cspHandler = $this->getCspHandler();
        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertSame("script-src 'self'", $response->headers->get('Content-Security-Policy'));

        $cspHandler->setUseLegacyHeaders(true);
        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertSame("script-src 'self'", $response->headers->get('X-Content-Security-Policy'));

        $cspHandler->setUseLegacyHeaders(false);
        $cspHandler->setReportOnly(true);

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertSame("script-src 'self'", $response->headers->get('Content-Security-Policy-Report-Only'));

        $cspHandler->setUseLegacyHeaders(true);
        $cspHandler->setReportOnly(true);

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertSame("script-src 'self'", $response->headers->get('X-Content-Security-Policy-Report-Only'));
    }

    private function getCspHandler(array $directives = ['script-src' => "'self'"]): CspHandler
    {
        $directiveSet = new DirectiveSet(new PolicyManager());
        $directiveSet->setDirectives($directives);
        $directiveSet->setLevel1Fallback(false);

        return new CspHandler($directiveSet);
    }
}
