<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Csp\WysiwygStyleProcessor;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\Studio\FigureRenderer;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendTemplate;
use Contao\System;
use Contao\Template;
use Nelmio\SecurityBundle\ContentSecurityPolicy\DirectiveSet;
use Nelmio\SecurityBundle\ContentSecurityPolicy\PolicyManager;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\VarDumper\VarDumper;
use Twig\Environment;

class TemplateTest extends TestCase
{
    public function testDelegatesRenderingToTwig(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/test_template.html.twig',
                $this->callback(function(array $context) {
                    $this->assertArrayHasKey('foo', $context);
                    $this->assertSame('bar', $context['foo']);

                    return true;
                })
            )
            ->willReturn('<output>');
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('twig', $twig);

        System::setContainer($container);

        $template = new BackendTemplate('test_template');
        $template->setData(['foo' => 'bar']);

        $this->assertSame('<output>', $template->parse());
    }

    public function testDoesNotModifyAbsoluteAssetUrl(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages
            ->expects($this->once())
            ->method('getUrl')
            ->with('/path/to/asset', 'package_name')
            ->willReturn('https://cdn.example.com/path/to/asset')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('assets.packages', $packages);

        System::setContainer($container);

        $template = new FrontendTemplate();
        $url = $template->asset('/path/to/asset', 'package_name');

        $this->assertSame('https://cdn.example.com/path/to/asset', $url);
    }

    public function testCanDumpTemplateVars(): void
    {
        $template = new FrontendTemplate();
        $template->setData(['test' => 1]);

        $dump = null;

        VarDumper::setHandler(
            static function ($var) use (&$dump): void {
                $dump = $var;
            },
        );

        $template->dumpTemplateVars();

        $this->assertSame(['test' => 1], $dump);
    }

    public function testFigureFunction(): void
    {
        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($this->once())
            ->method('render')
            ->with('123', '_my_size', ['foo' => 'bar'], 'my_template')
            ->willReturn('<result>')
        ;

        $container = $this->getContainerWithContaoConfiguration($this->getFixturesDir());
        $container->set('contao.image.studio.figure_renderer', $figureRenderer);

        System::setContainer($container);

        $this->assertSame('<result>', (new FrontendTemplate())->figure('123', '_my_size', ['foo' => 'bar'], 'my_template'));
    }

    public function testFigureFunctionUsesImageTemplateByDefault(): void
    {
        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($this->once())
            ->method('render')
            ->with(1, null, [], 'image')
            ->willReturn('<result>')
        ;

        $container = $this->getContainerWithContaoConfiguration($this->getFixturesDir());
        $container->set('contao.image.studio.figure_renderer', $figureRenderer);

        System::setContainer($container);

        (new FrontendTemplate())->figure(1, null);
    }

    #[DataProvider('provideBuffer')]
    public function testCompileReplacesLiteralInsertTags(string $buffer, string $expectedOutput): void
    {
        $page = new \stdClass();
        $page->minifyMarkup = false;

        $GLOBALS['objPage'] = $page;

        $template = new class($buffer) extends FrontendTemplate {
            public function __construct(private readonly string|null $testBuffer)
            {
                parent::__construct();
            }

            public function parse(): string
            {
                return $this->testBuffer;
            }

            public function testCompile(): string
            {
                $this->getResponse();

                return $this->strBuffer;
            }

            public static function replaceDynamicScriptTags($strBuffer)
            {
                return $strBuffer; // ignore dynamic script tags
            }
        };

        $this->assertSame($expectedOutput, $template->testCompile());

        unset($GLOBALS['objPage']);
    }

    public static function provideBuffer(): iterable
    {
        yield 'plain string' => [
            'foo bar',
            'foo bar',
        ];

        yield 'literal insert tags are replaced' => [
            'foo[{]bar[{]baz[}]',
            'foo&#123;&#123;bar&#123;&#123;baz&#125;&#125;',
        ];

        yield 'literal insert tags inside script tag are not replaced' => [
            '<script type="application/javascript">if (/[\[{]$/.test(foo)) {}</script>',
            '<script type="application/javascript">if (/[\[{]$/.test(foo)) {}</script>',
        ];

        yield 'multiple occurrences' => [
            '[{][}]<script>[{][}]</script>[{][}]<script>[{][}]</script>[{][}]',
            '&#123;&#123;&#125;&#125;<script>[{][}]</script>&#123;&#123;&#125;&#125;<script>[{][}]</script>&#123;&#123;&#125;&#125;',
        ];
    }

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

        System::getContainer()->set('contao.routing.response_context_accessor', $responseContextAccessor);

        $this->assertNotNull((new FrontendTemplate())->nonce('script-src'));
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

        System::getContainer()->set('contao.routing.response_context_accessor', $responseContextAccessor);
        System::getContainer()->set('request_stack', new RequestStack());

        (new FrontendTemplate())->addCspSource('script-src', 'https://example.com/files/foo/foobar.js');

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

        System::getContainer()->set('contao.routing.response_context_accessor', $responseContextAccessor);
        System::getContainer()->set('request_stack', new RequestStack());

        $script = 'this.form.requestSubmit()';
        $algorithm = 'sha384';

        (new FrontendTemplate())->addCspHash('script-src', $script, $algorithm);

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $expectedHash = base64_encode(hash($algorithm, $script, true));

        $this->assertSame(\sprintf("script-src 'self' '%s-%s'", $algorithm, $expectedHash), $response->headers->get('Content-Security-Policy'));
    }

    public function testAddsCspInlineStyleHash(): void
    {
        $directives = new DirectiveSet(new PolicyManager());
        $directives->setLevel1Fallback(false);
        $directives->setDirective('style-src', "'self'");

        $cspHandler = new CspHandler($directives);
        $responseContext = (new ResponseContext())->add($cspHandler);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        System::getContainer()->set('contao.routing.response_context_accessor', $responseContextAccessor);
        System::getContainer()->set('request_stack', new RequestStack());

        $style = 'display:none';
        $algorithm = 'sha384';

        $result = (new FrontendTemplate())->cspUnsafeInlineStyle($style, $algorithm);

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $expectedHash = base64_encode(hash($algorithm, $style, true));

        $this->assertSame($style, $result);
        $this->assertSame(\sprintf("style-src 'self' 'unsafe-hashes' '%s-%s'", $algorithm, $expectedHash), $response->headers->get('Content-Security-Policy'));
    }

    public function testOnceHelperExecutesCodeOnce(): void
    {
        $invocationCount = 0;

        $expensiveFunction = static function () use (&$invocationCount) {
            ++$invocationCount;

            return false;
        };

        $template = new FrontendTemplate();
        $template->hasFoo = Template::once($expensiveFunction);

        $this->assertFalse($template->hasFoo, 'first call');
        $this->assertFalse($template->hasFoo, 'second call');

        $this->assertSame(1, $invocationCount);
    }
}
