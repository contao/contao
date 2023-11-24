<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\Search;
use Contao\System;
use Contao\TestCase\ContaoDatabaseTrait;
use Contao\TestCase\FunctionalTestCase;

class SearchTest extends FunctionalTestCase
{
    use ContaoDatabaseTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANGUAGE'] = 'en';

        System::setContainer(static::getContainer());
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANGUAGE']);

        parent::tearDown();
    }

    public function testQuery(): void
    {
        $this->indexPage('page1', 'Page1 Content');
        $this->indexPage('page2', 'Page2 Content');
        $this->indexPage('page3', 'Page3 Content');

        $this->assertSame(1, Search::query('PAGE1')->getCount());
        $this->assertSame(0, Search::query('PAGE')->getCount());
        $this->assertSame(3, Search::query('PAGE*')->getCount());
        $this->assertSame(1, Search::query('content,Page1*')->getCount());
        $this->assertSame(3, Search::query('content,Page*')->getCount());
        $this->assertSame(3, Search::query('content,Page*')->getCount());

        $this->assertSame(0, Search::query('page1 page2')->getCount());
        $this->assertSame(2, Search::query('page1 page2', true)->getCount());

        $this->indexPage('accents1', 'preter cafe a');
        $this->indexPage('accents2', 'preter café à');
        $this->indexPage('accents3', 'prêter café à');

        $this->assertSame(3, Search::query('cafe')->getCount());
        $this->assertSame(3, Search::query('PRÊTER CAFÉ')->getCount());
        $this->assertSame(3, Search::query('prêter cafe')->getCount());
        $this->assertSame(3, Search::query('*rêter *afe"')->getCount());
        $this->assertSame(3, Search::query('*afé"')->getCount());

        // Exact behavior of phrase searches depends on the COLLATION support in MySQL REGEXP
        $this->assertGreaterThan(0, Search::query('"preter cafe"')->getCount());

        $this->indexPage('numbers1', '123 ABC');
        $this->indexPage('numbers2', '１２３ ＡＢＣ');

        $this->assertSame(2, Search::query('123')->getCount());
        $this->assertSame(2, Search::query('１２３')->getCount());
        $this->assertSame(2, Search::query('123 abc')->getCount());
        $this->assertSame(2, Search::query('１２３ ＡＢＣ')->getCount());
        $this->assertSame(2, Search::query('ABC')->getCount());
        $this->assertSame(2, Search::query('ＡＢＣ')->getCount());
        $this->assertSame(2, Search::query('ａｂｃ')->getCount());
    }

    public function testRemoveEntry(): void
    {
        $this->indexPage('page1', 'Page1 Content');
        $this->indexPage('page2', 'Page2 Content');
        $this->indexPage('page3', 'Page3 Content');

        $this->assertSame(3, Search::query('Page*')->getCount());

        Search::removeEntry('https://contao.wip/page1');

        $this->assertSame(2, Search::query('Page*')->getCount());

        Search::removeEntry('https://contao.wip/page3');

        $this->assertSame(1, Search::query('Page*')->getCount());
    }

    private function indexPage(string $url, string $content): void
    {
        Search::indexPage([
            'url' => "https://contao.wip/$url",
            'content' => '<head><meta name="description" content=""><meta name="keywords" content=""></head><body>'.$content,
            'protected' => false,
            'groups' => '',
            'pid' => '1',
            'title' => '',
            'language' => 'en',
            'meta' => [],
        ]);
    }
}
