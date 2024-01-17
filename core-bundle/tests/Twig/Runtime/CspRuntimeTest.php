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

use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\CspRuntime;
use Nelmio\SecurityBundle\ContentSecurityPolicy\DirectiveSet;
use Nelmio\SecurityBundle\ContentSecurityPolicy\PolicyManager;
use Symfony\Component\HttpFoundation\Response;

class CspRuntimeTest extends TestCase
{
    public function testRetrievesNonceFromCspBuilder(): void
    {
        $directives = new DirectiveSet(new PolicyManager());
        $directives->setDirective('script-src', "'self'");

        $cspHandler = new CspHandler($directives);
        $responseContext = (new ResponseContext())->add($cspHandler);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        $runtime = new CspRuntime($responseContextAccessor);

        $this->assertNotNull($runtime->getNonce('script-src'));
    }

    public function testAddsCspSource(): void
    {
        $directives = new DirectiveSet(new PolicyManager());
        $directives->setDirective('script-src', "'self'");

        $cspHandler = new CspHandler($directives);
        $responseContext = (new ResponseContext())->add($cspHandler);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        $runtime = new CspRuntime($responseContextAccessor);

        $runtime->addSource('script-src', 'https://example.com/files/foo/foobar.js');

        $this->assertSame("'self' https://example.com/files/foo/foobar.js", $directives->getDirective('script-src'));
    }

    public function testAddsCspHash(): void
    {
        $directives = new DirectiveSet(new PolicyManager());
        $directives->setLevel1Fallback(false);
        $directives->setDirective('script-src', "'self'");

        $cspHandler = new CspHandler($directives);
        $responseContext = (new ResponseContext())->add($cspHandler);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        $script = 'this.form.requestSubmit()';
        $algorithm = 'sha384';

        $runtime = new CspRuntime($responseContextAccessor);
        $runtime->addHash('script-src', $script, $algorithm);

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $expectedHash = base64_encode(hash($algorithm, $script, true));

        $this->assertSame(sprintf("script-src 'self' '%s-%s'", $algorithm, $expectedHash), $response->headers->get('Content-Security-Policy'));
    }
}
