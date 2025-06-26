<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext\JsonLd;

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use PHPUnit\Framework\TestCase;

class ContaoPageSchemaTest extends TestCase
{
    public function testGeneralSettersAndGetters(): void
    {
        $schema = new ContaoPageSchema('title', 42, false, false, [1, 2, 3], false, [2]);

        $this->assertSame('title', $schema->getTitle());
        $this->assertSame(42, $schema->getPageId());
        $this->assertFalse($schema->isNoSearch());
        $this->assertFalse($schema->isProtected());
        $this->assertSame([1, 2, 3], $schema->getGroups());
        $this->assertSame([2], $schema->getMemberGroups());
        $this->assertSame('', $schema->getSearchIndexer());
        $this->assertFalse($schema->isFePreview());

        $schema->setTitle('Foobar');
        $this->assertSame('Foobar', $schema->getTitle());

        $schema->setPageId(43);
        $this->assertSame(43, $schema->getPageId());

        $schema->setNoSearch(true);
        $this->assertTrue($schema->isNoSearch());

        $schema->setProtected(true);
        $this->assertTrue($schema->isProtected());

        $schema->setGroups([42, 43]);
        $this->assertSame([42, 43], $schema->getGroups());

        $schema->setFePreview(true);
        $this->assertTrue($schema->isFePreview());

        $schema->setSearchIndexer('');
        $schema->setNoSearch(false);
        $this->assertSame('', $schema->getSearchIndexer());
        $this->assertFalse($schema->isNoSearch());

        $schema->setSearchIndexer('always_index');
        $this->assertSame('always_index', $schema->getSearchIndexer());
        $this->assertFalse($schema->isNoSearch());

        $schema->setSearchIndexer('never_index');
        $this->assertSame('never_index', $schema->getSearchIndexer());
        $this->assertTrue($schema->isNoSearch());
    }

    public function testUpdateFromHtmlHeadBag(): void
    {
        $schema = new ContaoPageSchema('title', 42, false, false, [], false);
        $schema->updateFromHtmlHeadBag((new HtmlHeadBag())->setTitle('Foobar'));

        $this->assertSame('Foobar', $schema->getTitle());
    }
}
