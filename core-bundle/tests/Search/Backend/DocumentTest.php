<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend;

use Contao\CoreBundle\Search\Backend\Document;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function testDocument(): void
    {
        $document = (new Document('id', 'type', 'searchContent'))
            ->withMetadata(['meta' => 'data', 'i-should-get-stripped-because-non-utf8' => "\x80"])
            ->withTags(['tag-one', 'tag-two'])
        ;

        $this->assertSame('id', $document->getId());
        $this->assertSame('type', $document->getType());
        $this->assertSame('searchContent', $document->getSearchableContent());
        $this->assertSame(['meta' => 'data'], $document->getMetadata());
        $this->assertSame(['tag-one', 'tag-two'], $document->getTags());
    }
}
