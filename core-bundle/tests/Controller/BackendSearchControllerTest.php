<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\BackendSearchController;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\Query;
use Contao\CoreBundle\Search\Backend\Result;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;

class BackendSearchControllerTest extends TestCase
{
    public function testSends404OnInvalidUser(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $controller = new BackendSearchController(
            $this->mockSecurityHelper(false),
            $this->createMock(BackendSearch::class),
        );

        $controller(new Request());
    }

    #[DataProvider('provideRequests')]
    public function testStreamResultWithQueries(Request $request, Query $expectedQuery): void
    {
        $hits = [
            new Hit(new Document('id', 'type', 'content'), 'title', 'view-url'),
        ];

        $backendSearch = $this->createMock(BackendSearch::class);
        $backendSearch
            ->expects($this->once())
            ->method('search')
            ->with($this->callback(static fn (Query $query) => $query->equals($expectedQuery)))
            ->willReturn(new Result($hits))
        ;

        $controller = new BackendSearchController(
            $this->mockSecurityHelper(),
            $backendSearch,
        );

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/search/show_results.stream.html.twig',
                $this->callback(static fn (array $parameters) => $parameters === ['hits' => $hits]),
            )
            ->willReturn('<stream>')
        ;

        $container = new ContainerBuilder();
        $container->set('twig', $twig);
        $container->set('request_stack', new RequestStack([$request]));

        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('<stream>', $response->getContent());
        $this->assertSame('turbo_stream', $request->getRequestFormat());
    }

    public static function provideRequests(): iterable
    {
        $getTurboStreamRequest = static function (array $query = []): Request {
            $request = new Request($query);
            $request->headers->set('Accept', 'text/vnd.turbo-stream.html; charset=utf-8');

            return $request;
        };

        yield 'Test defaults' => [
            $getTurboStreamRequest(),
            new Query(20, null, null, null),
        ];

        yield 'Test keywords' => [
            $getTurboStreamRequest(['keywords' => 'test']),
            new Query(20, 'test', null, null),
        ];

        yield 'Test some more parameters' => [
            $getTurboStreamRequest(['perPage' => 30, 'type' => 'foobar', 'tag' => 'other']),
            new Query(30, null, 'foobar', 'other'),
        ];
    }

    private function mockSecurityHelper(bool $granted = true): Security&MockObject
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->willReturn($granted)
        ;

        return $security;
    }
}
