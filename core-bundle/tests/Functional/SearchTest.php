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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class SearchTest extends FunctionalTestCase
{
    use ContaoDatabaseTrait;
    use ExpectDeprecationTrait;

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

    /**
     * @group legacy
     */
    public function testSearchFor(): void
    {
        $this->expectDeprecation('%ssearchFor%shas been deprecated%s');

        $this->indexPage('page1', 'Page1 Content');
        $this->indexPage('page2', 'Page2 Content');
        $this->indexPage('page3', 'Page3 Content');

        $this->assertSame(1, Search::searchFor('PAGE1')->count());
        $this->assertSame(0, Search::searchFor('PAGE')->count());
        $this->assertSame(3, Search::searchFor('PAGE*')->count());
        $this->assertSame(1, Search::searchFor('content,Page1*')->count());
        $this->assertSame(3, Search::searchFor('content,Page*')->count());
        $this->assertSame(3, Search::searchFor('content,Page*')->count());

        $this->assertSame(0, Search::searchFor('page1 page2')->count());
        $this->assertSame(2, Search::searchFor('page1 page2', true)->count());

        $this->indexPage('accents1', 'preter cafe a');
        $this->indexPage('accents2', 'preter café à');
        $this->indexPage('accents3', 'prêter café à');

        $this->assertSame(3, Search::searchFor('cafe')->count());
        $this->assertSame(3, Search::searchFor('PRÊTER CAFÉ')->count());
        $this->assertSame(3, Search::searchFor('prêter cafe')->count());
        $this->assertSame(3, Search::searchFor('*rêter *afe"')->count());
        $this->assertSame(3, Search::searchFor('*afé"')->count());

        // Exact behavior of phrase searches depends on the COLLATION support in MySQL REGEXP
        $this->assertGreaterThan(0, Search::searchFor('"preter cafe"')->count());

        $this->indexPage('numbers1', '123 ABC');
        $this->indexPage('numbers2', '１２３ ＡＢＣ');

        $this->assertSame(2, Search::searchFor('123')->count());
        $this->assertSame(2, Search::searchFor('１２３')->count());
        $this->assertSame(2, Search::searchFor('123 abc')->count());
        $this->assertSame(2, Search::searchFor('１２３ ＡＢＣ')->count());
        $this->assertSame(2, Search::searchFor('ABC')->count());
        $this->assertSame(2, Search::searchFor('ＡＢＣ')->count());
        $this->assertSame(2, Search::searchFor('ａｂｃ')->count());
    }

    /**
     * @group legacy
     */
    public function testRemoveEntry(): void
    {
        $this->expectDeprecation('%ssearchFor%shas been deprecated%s');

        $this->indexPage('page1', 'Page1 Content');
        $this->indexPage('page2', 'Page2 Content');
        $this->indexPage('page3', 'Page3 Content');

        $this->assertSame(3, Search::searchFor('Page*')->count());

        Search::removeEntry('https://contao.wip/page1');

        $this->assertSame(2, Search::searchFor('Page*')->count());

        Search::removeEntry('https://contao.wip/page3');

        $this->assertSame(1, Search::searchFor('Page*')->count());
    }

    private function indexPage(string $url, string $content): void
    {
        Search::indexPage([
            'url' => "https://contao.wip/$url",
            'content' => '<head><meta name="description" content=""><meta name="keywords" content=""></head><body>'.$content,
            'protected' => '',
            'groups' => '',
            'pid' => '1',
            'title' => '',
            'language' => 'en',
            'meta' => [],
        ]);
    }
}
