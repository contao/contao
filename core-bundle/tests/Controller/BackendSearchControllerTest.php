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
use Contao\CoreBundle\Search\Backend\Query;
use Contao\CoreBundle\Search\Backend\Result;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
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
            $this->createMock(Environment::class),
        );

        $controller(new Request());
    }

    /**
     * @dataProvider delegatesRequestCorrectlyProvider
     */
    public function testDelegatesRequestCorrectly(Request $request, Query $expectedQuery): void
    {
        $backendSearch = $this->createMock(BackendSearch::class);
        $backendSearch
            ->expects($this->once())
            ->method('search')
            ->with($this->callback(static fn (Query $query) => $query->equals($expectedQuery)))
            ->willReturn(new Result([]))
        ;

        $controller = new BackendSearchController(
            $this->mockSecurityHelper(true),
            $backendSearch,
            $this->createMock(Environment::class),
        );

        $controller($request);
    }

    public static function delegatesRequestCorrectlyProvider(): iterable
    {
        yield 'Test defaults' => [
            new Request(),
            new Query(20, null, null, null),
        ];

        yield 'Test keywords' => [
            new Request(['keywords' => 'test']),
            new Query(20, 'test', null, null),
        ];

        yield 'Test some more parameters' => [
            new Request(['perPage' => 30, 'type' => 'foobar', 'tag' => 'other']),
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
