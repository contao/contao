<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\File;

use Contao\CoreBundle\File\MetaData;
use PHPUnit\Framework\TestCase;

class MetaDataTest extends TestCase
{
    public function testCreatesMetaDataContainer(): void
    {
        $metaData = new MetaData([
            'link' => 'foo://bar',
            'foo' => 'bar',
        ]);

        $this->assertSame('foo://bar', $metaData->getUrl());
        $this->assertSame('', $metaData->getTitle());
        $this->assertSame('bar', $metaData->get('foo'));

        $this->assertSame([
            MetaData::VALUE_URL => 'foo://bar',
            'foo' => 'bar',
        ], $metaData->all());
    }

    public function testMergesWithOtherMetaDataContainer(): void
    {
        $first = new MetaData([
            MetaData::VALUE_TITLE => 'foo title',
            MetaData::VALUE_CAPTION => 'foo caption',
        ]);

        $second = new MetaData([
            MetaData::VALUE_TITLE => 'bar title',
            MetaData::VALUE_ALT => 'foo alt',
        ]);

        // Second must have precedence if keys are the same
        $result = $first->withOther($second);

        $this->assertSame([
            'title' => 'bar title',
            'caption' => 'foo caption',
            'alt' => 'foo alt',
        ], $result->all());

        // Original containers must not be altered
        $this->assertSame('foo title', $first->getTitle());
        $this->assertSame('', $second->getCaption());
    }
}
