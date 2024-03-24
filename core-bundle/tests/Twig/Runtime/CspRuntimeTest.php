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

use Contao\CoreBundle\Csp\WysiwygStyleProcessor;
use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\String\HtmlAttributes;
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

        $runtime = new CspRuntime($responseContextAccessor, new WysiwygStyleProcessor([]));

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

        $runtime = new CspRuntime($responseContextAccessor, new WysiwygStyleProcessor([]));
        $runtime->addSource('script-src', 'https://example.com/files/foo/foobar.js');

        $this->assertSame("'self' https://example.com/files/foo/foobar.js", $directives->getDirective('script-src'));
    }

    public function testAddsMultipleCspSources(): void
    {
        $directives = new DirectiveSet(new PolicyManager());
        $directives->setDirective('script-src', "'self'");
        $directives->setDirective('style-src', "'self'");

        $cspHandler = new CspHandler($directives);
        $responseContext = (new ResponseContext())->add($cspHandler);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        $runtime = new CspRuntime($responseContextAccessor, new WysiwygStyleProcessor([]));
        $runtime->addSource(['script-src', 'style-src'], 'https://cdn.example.com/');

        $this->assertSame("'self' https://cdn.example.com/", $directives->getDirective('script-src'));
        $this->assertSame("'self' https://cdn.example.com/", $directives->getDirective('style-src'));
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

        $runtime = new CspRuntime($responseContextAccessor, new WysiwygStyleProcessor([]));
        $runtime->addHash('script-src', $script, $algorithm);

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $expectedHash = base64_encode(hash($algorithm, $script, true));

        $this->assertSame(sprintf("script-src 'self' '%s-%s'", $algorithm, $expectedHash), $response->headers->get('Content-Security-Policy'));
    }

    public function testAddsCspHashFromUnsafeInlineStyle(): void
    {
        $directives = new DirectiveSet(new PolicyManager());
        $directives->setDirective('style-src', "'self'");

        $cspHandler = new CspHandler($directives);
        $responseContext = (new ResponseContext())->add($cspHandler);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->exactly(2))
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        $runtime = new CspRuntime($responseContextAccessor, new WysiwygStyleProcessor([]));
        $this->assertSame('foobar', $runtime->unsafeInlineStyle('foobar'));

        $attrs = new HtmlAttributes('style="color:red"');
        $runtime = new CspRuntime($responseContextAccessor, new WysiwygStyleProcessor([]));
        $this->assertSame($attrs, $runtime->unsafeInlineStyle($attrs));

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertSame(
            "style-src 'self' 'unsafe-hashes' 'unsafe-inline' 'sha256-w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI=' 'sha256-ZBTj5RHLnrF+IxdRZM2RuLfjTJQXNSi7fLQHr09onfY='",
            $response->headers->get('Content-Security-Policy'),
        );
    }

    public function testCallsWysiwygProcessor(): void
    {
        $directives = new DirectiveSet(new PolicyManager());
        $directives->setDirective('style-src', "'self'");

        $cspHandler = new CspHandler($directives);
        $responseContext = (new ResponseContext())->add($cspHandler);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->exactly(2))
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        $wysiwygProcessor = $this->createMock(WysiwygStyleProcessor::class);
        $wysiwygProcessor
            ->expects($this->once())
            ->method('extractStyles')
            ->with('foobar')
            ->willReturn(['foobarstyle'])
        ;

        $runtime = new CspRuntime($responseContextAccessor, $wysiwygProcessor);
        $this->assertSame('foobar', $runtime->inlineStyles('foobar'));

        $wysiwygProcessor = $this->createMock(WysiwygStyleProcessor::class);
        $wysiwygProcessor
            ->expects($this->once())
            ->method('extractStyles')
            ->with('<div style="color: red;"></div>')
            ->willReturn(['color: red;'])
        ;

        $attrs = new HtmlAttributes('style="color:red"');
        $runtime = new CspRuntime($responseContextAccessor, $wysiwygProcessor);
        $this->assertSame($attrs, $runtime->inlineStyles($attrs));

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertSame(
            "style-src 'self' 'unsafe-hashes' 'unsafe-inline' 'sha256-G9KEe21cICJs7ADRF9jwf63CdC5OJI1mO2LVlv63cUY=' 'sha256-ZBTj5RHLnrF+IxdRZM2RuLfjTJQXNSi7fLQHr09onfY='",
            $response->headers->get('Content-Security-Policy'),
        );
    }
}
