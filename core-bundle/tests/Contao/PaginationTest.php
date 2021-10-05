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

/**
 * Needs to run in a separate process because it includes the functions.php file.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PaginationTest extends TestCase
{
    protected function setUp(): void
    {
        include_once __DIR__.'/../../src/Resources/contao/helper/functions.php';

        $GLOBALS['TL_LANG']['MSC']['first'] = '';
        $GLOBALS['TL_LANG']['MSC']['previous'] = '';
        $GLOBALS['TL_LANG']['MSC']['next'] = '';
        $GLOBALS['TL_LANG']['MSC']['last'] = '';
        $GLOBALS['TL_LANG']['MSC']['totalPages'] = '';
        $GLOBALS['TL_LANG']['MSC']['goToPage'] = '';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $_GET['page']);

        Input::resetCache();
        Input::resetUnusedGet();

        parent::tearDown();
    }

    /**
     * @dataProvider paginationDataProvider
     */
    public function testGeneratesPaginationItems(array $data): void
    {
        $_GET['page'] = $data['currentPage'] ?? 1;

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
