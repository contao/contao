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
    public function testCreateAndAccessMetaDataContainer(): void
    {
        $metaData = new MetaData([
            'link' => 'foo://bar',
            'foo' => 'bar',
        ]);

        $this->assertFalse($metaData->empty());

        $this->assertSame('foo://bar', $metaData->getUrl());
        $this->assertSame('', $metaData->getTitle());
        $this->assertSame('bar', $metaData->get('foo'));

        $this->assertSame([
            MetaData::VALUE_URL => 'foo://bar',
            'foo' => 'bar',
        ], $metaData->all());
    }

    public function testEmpty(): void
    {
        $metaData = new MetaData([]);

        $this->assertTrue($metaData->empty());
    }

    public function testHas(): void
    {
        $metaData = new MetaData([
            MetaData::VALUE_ALT => '',
            'foo' => 'bar',
        ]);

        $this->assertTrue($metaData->has(MetaData::VALUE_ALT));
        $this->assertTrue($metaData->has('foo'));
        $this->assertFalse($metaData->has('bar'));
    }
}
