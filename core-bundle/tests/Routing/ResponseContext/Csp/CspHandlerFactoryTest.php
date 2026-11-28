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

use Contao\CoreBundle\Controller\CspReporterController;
use Contao\CoreBundle\Csp\CspParser;
use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandlerFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Nelmio\SecurityBundle\ContentSecurityPolicy\PolicyManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class CspHandlerFactoryTest extends TestCase
{
    public function testCreatesHandlerFromHeader(): void
    {
        $factory = new CspHandlerFactory(new CspParser(new PolicyManager()), $this->createMock(UrlGeneratorInterface::class));
        $handler = $factory->create("script-src 'self'; report-uri https://example.com/report");

        $this->assertSame("'self'", $handler->getDirective('script-src'));
        $this->assertSame('https://example.com/report', $handler->getDirective('report-uri'));
        $this->assertFalse($handler->getReportOnly());
    }

    public function testDoesNotCreateHandlerIfCspIsDisabled(): void
    {
        $parser = $this->createMock(CspParser::class);
        $parser
            ->expects($this->never())
            ->method('parseHeader')
        ;

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->never())
            ->method('generate')
        ;

        $page = $this->mockClassWithProperties(PageModel::class, ['enableCsp' => false]);
        $factory = new CspHandlerFactory($parser, $urlGenerator);

        $this->assertNull($factory->createFromPage($page));
    }

    /**
     * @dataProvider getReportOnlySettings
     */
    public function testCreatesHandlerFromPage(bool $reportOnly): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->never())
            ->method('generate')
        ;

        $page = $this->mockClassWithProperties(PageModel::class, [
            'enableCsp' => true,
            'csp' => "script-src 'self'; report-uri https://example.com/report",
            'cspReportOnly' => $reportOnly,
            'cspReportLog' => false,
        ]);

        $factory = new CspHandlerFactory(new CspParser(new PolicyManager()), $urlGenerator);
        $handler = $factory->createFromPage($page);
        $nonce = $handler->getNonce('script-src');
        $response = new Response();
        $handler->applyHeaders($response);

        $headerName = 'Content-Security-Policy'.($reportOnly ? '-Report-Only' : '');

        $this->assertSame($reportOnly, $handler->getReportOnly());
        $this->assertNotNull($nonce);
        $this->assertSame("script-src 'self' 'nonce-".$nonce."'; report-uri https://example.com/report", $response->headers->get($headerName));
    }

    public static function getReportOnlySettings(): iterable
    {
        yield [false];
        yield [true];
    }

    /**
     * @dataProvider getReportRouteAvailability
     */
    public function testConfiguresReportUriAndRestoresPreviewBaseUrl(bool $routeExists): void
    {
        $context = new RequestContext('/preview.php');
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('getContext')
            ->willReturn($context)
        ;
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(CspReporterController::class, ['page' => 42], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturnCallback(
                function () use ($context, $routeExists): string {
                    $this->assertSame('', $context->getBaseUrl());

                    if (!$routeExists) {
                        throw new RouteNotFoundException();
                    }

                    return 'https://example.com/csp/report';
                },
            )
        ;

        $page = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'enableCsp' => true,
            'csp' => "script-src 'self'; report-uri https://example.com/original",
            'cspReportOnly' => false,
            'cspReportLog' => true,
        ]);

        $factory = new CspHandlerFactory(new CspParser(new PolicyManager()), $urlGenerator);
        $handler = $factory->createFromPage($page);

        $this->assertSame('/preview.php', $context->getBaseUrl());
        $this->assertSame("'self'", $handler->getDirective('script-src'));
        $this->assertSame($routeExists ? 'https://example.com/csp/report' : 'https://example.com/original', $handler->getDirective('report-uri'));
    }

    public static function getReportRouteAvailability(): iterable
    {
        yield 'report route exists' => [true];
        yield 'report route is missing' => [false];
    }
}
