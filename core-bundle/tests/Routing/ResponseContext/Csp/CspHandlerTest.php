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
        $cspHandler = $this->getCspHandler(['default-src' => "'self' foobar.com", 'frame-src' => "'self'"]);
        $cspHandler->addSource('frame-src', 'www.youtube.com');
        $cspHandler->addSource('img-src', 'data:');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertStringContainsString("default-src 'self' foobar.com; frame-src 'self' www.youtube.com; img-src 'self' foobar.com data:", $response->headers->get('Content-Security-Policy'));
    }

    public function testChecksIfDiretiveOrFallbackIsSet(): void
    {
        $cspHandler = $this->getCspHandler(['default-src' => "'self'"]);
        $this->assertNotNull($cspHandler->getDirective('script-src'));

        $cspHandler = $this->getCspHandler(['default-src' => "'self'"]);
        $this->assertNull($cspHandler->getDirective('script-src', false));
    }

    public function testAppliesHeaders(): void
    {
        $cspHandler = $this->getCspHandler();
        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertSame("script-src 'self'", $response->headers->get('Content-Security-Policy'));

        $cspHandler->setReportOnly(true);
        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertSame("script-src 'self'", $response->headers->get('Content-Security-Policy-Report-Only'));
    }

    private function getCspHandler(array $directives = ['script-src' => "'self'"]): CspHandler
    {
        $directiveSet = new DirectiveSet(new PolicyManager());
        $directiveSet->setDirectives($directives);
        $directiveSet->setLevel1Fallback(false);

        return new CspHandler($directiveSet);
    }
}
