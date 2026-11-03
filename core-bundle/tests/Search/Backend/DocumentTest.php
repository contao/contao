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
            ->withMetadata([
                'meta' => 'data',
                'i-should-get-stripped-because-non-utf8' => "\x80",
                'recursive' => [
                    'also' => 'works',
                    'i-should-get-stripped-because-non-utf8' => "\x80",
                ],
            ])
            ->withTags(['tag-one', 'tag-two'])
        ;

        $originalDocument = $document;
        $document = $document->withAddedSearchableContent('more data');

        $this->assertSame('id', $document->getId());
        $this->assertSame('type', $document->getType());
        $this->assertSame('searchContent', $originalDocument->getSearchableContent());
        $this->assertSame('searchContent more data', $document->getSearchableContent());
        $this->assertSame(['meta' => 'data', 'recursive' => ['also' => 'works']], $document->getMetadata());
        $this->assertSame(['tag-one', 'tag-two'], $document->getTags());
    }

    public function testAddedSearchableContentHandlesEmptyContent(): void
    {
        $document = new Document('id', 'type', '');

        $this->assertSame('more data', $document->withAddedSearchableContent('more data')->getSearchableContent());
        $this->assertSame('', $document->withAddedSearchableContent('')->getSearchableContent());
        $this->assertSame('content', $document->withSearchableContent('content')->withAddedSearchableContent('')->getSearchableContent());
    }
}
