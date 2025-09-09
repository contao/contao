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

use Contao\CoreBundle\Exception\PageOutOfRangeException;
use Contao\CoreBundle\Pagination\Pagination;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PaginationTest extends TestCase
{
    public function testCreatesPagination(): void
    {
        $request = Request::create('/foobar?page=2');

        $pagination = new Pagination($request, 'page', 100, 5, 10);

        $this->assertSame(2, $pagination->getCurrent());
        $this->assertSame(20, $pagination->getPageCount());
        $this->assertSame(100, $pagination->getTotal());
        $this->assertNull($pagination->getFirst());
        $this->assertSame(20, $pagination->getLast());
        $this->assertSame(1, $pagination->getPrevious());
        $this->assertSame(3, $pagination->getNext());
        $this->assertSame(range(6, 10), $pagination->getItemsForPage(range(1, 100)));
        $this->assertSame(range(1, 10), $pagination->getPages());
        $this->assertSame('page', $pagination->getParam());
        $this->assertSame(5, $pagination->getPerPage());
        $this->assertSame('/foobar?page=3', $pagination->getUrlForPage(3));
    }

    public function testThrowsOutOfRangeException(): void
    {
        $this->expectException(PageOutOfRangeException::class);

        $request = Request::create('/foobar?page=20');

        new Pagination($request, 'page', 10, 5);
    }

    public function testDoesNotShowFirstLastPrevNext(): void
    {
        $pagination = new Pagination(new Request(), 'page', 10, 5, 10);

        $this->assertNull($pagination->getFirst());
        $this->assertNull($pagination->getPrevious());

        $pagination = new Pagination(Request::create('/foobar?page=2'), 'page', 10, 5, 10);

        $this->assertNull($pagination->getNext());
        $this->assertNull($pagination->getLast());
    }
}
