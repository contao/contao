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

use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Hit;
use PHPUnit\Framework\TestCase;

class HitTest extends TestCase
{
    public function testHit(): void
    {
        $figureBuilder = $this->createStub(FigureBuilder::class);

        $hit = (new Hit(new Document('42', 'type', 'searchable'), 'title', 'https://example.com'))
            ->withContext('context')
            ->withImageFigureBuilder($figureBuilder)
            ->withEditUrl('https://example.com?edit=true')
            ->withMetadata(['foo' => 'bar'])
        ;

        $this->assertSame('title', $hit->getTitle());
        $this->assertSame('https://example.com', $hit->getViewUrl());
        $this->assertSame('context', $hit->getContext());
        $this->assertSame($figureBuilder, $hit->getImageFigureBuilder());
        $this->assertSame('https://example.com?edit=true', $hit->getEditUrl());
        $this->assertSame(['foo' => 'bar'], $hit->getMetadata());
        $this->assertSame('type', $hit->getVisibleType());

        $hit = $hit->withVisibleType('visible-type');
        $this->assertSame('visible-type', $hit->getVisibleType());
    }
}
