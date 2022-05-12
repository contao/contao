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

use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Pagination;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['first'] = 'First';
        $GLOBALS['TL_LANG']['MSC']['previous'] = 'Previous';
        $GLOBALS['TL_LANG']['MSC']['next'] = 'Next';
        $GLOBALS['TL_LANG']['MSC']['last'] = 'Last';
        $GLOBALS['TL_LANG']['MSC']['totalPages'] = 'Total';
        $GLOBALS['TL_LANG']['MSC']['goToPage'] = 'Go to';

        System::setContainer($this->getContainerWithContaoConfiguration());
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG']);
        $_GET = [];

        Input::setUnusedRouteParameters([]);

        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    /**
     * @dataProvider paginationDataProvider
     */
    public function testGeneratesPaginationItems(array $data): void
    {
        System::getContainer()->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request(['page' => $data['currentPage'] ?? 1]));

        $pagination = new Pagination($data['total'], $data['perPage'], $data['maxLinks'], 'page', $this->createMock(FrontendTemplate::class));
        $items = $pagination->getItemsAsArray();

        $this->assertCount($data['expectedCount'], $items);

        if ($data['expectedCount'] > 0) {
            $this->assertSame($data['lowestPage'], reset($items)['page']);
            $this->assertSame($data['highestPage'], end($items)['page']);
        }
    }

    public function paginationDataProvider(): \Generator
    {
        yield 'lower than limit' => [[
            'total' => 7,
            'perPage' => 2,
            'maxLinks' => 5,
            'expectedCount' => 4,
            'lowestPage' => 1,
            'highestPage' => 4,
        ]];

        yield 'matches limit' => [[
            'total' => 10,
            'perPage' => 2,
            'maxLinks' => 5,
            'expectedCount' => 5,
            'lowestPage' => 1,
            'highestPage' => 5,
        ]];

        yield 'even limit' => [[
            'total' => 10,
            'perPage' => 2,
            'maxLinks' => 4,
            'expectedCount' => 4,
            'lowestPage' => 1,
            'highestPage' => 4,
        ]];

        yield 'above limit' => [[
            'total' => 10,
            'perPage' => 2,
            'maxLinks' => 3,
            'expectedCount' => 3,
            'lowestPage' => 1,
            'highestPage' => 3,
        ]];

        yield 'somewhat in the middle' => [[
            'total' => 50,
            'perPage' => 5,
            'maxLinks' => 6,
            'expectedCount' => 6,
            'lowestPage' => 2,
            'highestPage' => 7,
            'currentPage' => 4,
        ]];

        yield 'on last page' => [[
            'total' => 15,
            'perPage' => 2,
            'maxLinks' => 4,
            'expectedCount' => 4,
            'lowestPage' => 5,
            'highestPage' => 8,
            'currentPage' => 8,
        ]];

        yield 'single page' => [[
            'total' => 8,
            'perPage' => 10,
            'maxLinks' => 5,
            'expectedCount' => 0,
        ]];

        yield 'no items' => [[
            'total' => 0,
            'perPage' => 10,
            'maxLinks' => 5,
            'expectedCount' => 0,
        ]];
    }
}
