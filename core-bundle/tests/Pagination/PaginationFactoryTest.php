<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\OptIn;

use Contao\CoreBundle\Pagination\PaginationFactory;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginationFactoryTest extends TestCase
{
    public function testCreatesPagination(): void
    {
        $request = Request::create('/foobar?page=2');

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $factory = new PaginationFactory($requestStack);

        $pagination = $factory->create('page', 10, 5);

        $this->assertSame(2, $pagination->getCurrent());
        $this->assertSame(2, $pagination->getPageCount());
        $this->assertSame(10, $pagination->getTotal());
    }
}
