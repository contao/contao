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

use Contao\ContentModel;
use Contao\CoreBundle\File\MetaData;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FilesModel;
use Contao\System;

class MetaDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());

        $GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields'] = [
            'title' => '', 'alt' => '', 'link' => '', 'caption' => '',
        ];
    }

    public function testCreateAndAccessMetaDataContainer(): void
    {
        $metaData = new MetaData([
            MetaData::VALUE_ALT => 'alt',
            MetaData::VALUE_CAPTION => 'caption',
            MetaData::VALUE_TITLE => 'title',
            MetaData::VALUE_URL => 'url',
            'foo' => 'bar',
        ]);

        $this->assertFalse($metaData->empty());

        $this->assertSame('alt', $metaData->getAlt());
        $this->assertSame('caption', $metaData->getCaption());
        $this->assertSame('title', $metaData->getTitle());
        $this->assertSame('url', $metaData->getUrl());
        $this->assertSame('bar', $metaData->get('foo'));

        $this->assertSame(
            [
                MetaData::VALUE_ALT => 'alt',
                MetaData::VALUE_CAPTION => 'caption',
                MetaData::VALUE_TITLE => 'title',
                MetaData::VALUE_URL => 'url',
                'foo' => 'bar',
            ],
            $metaData->all()
        );
    }

    public function testGetEmpty(): void
    {
        $metaData = new MetaData([]);

        $this->assertSame('', $metaData->getAlt());
        $this->assertSame('', $metaData->getCaption());
        $this->assertSame('', $metaData->getTitle());
        $this->assertSame('', $metaData->getUrl());

        $this->assertNull($metaData->get('foo'));
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

    public function testCreatesMetaDataContainerFromContentModel(): void
    {
        /** @var ContentModel $model */
        $model = (new \ReflectionClass(ContentModel::class))->newInstanceWithoutConstructor();

        $model->setRow([
            'id' => 100,
            'headline' => 'foobar',
            'overwriteMeta' => '1',
            'alt' => 'foo alt',
            'imageTitle' => 'foo title',
            'imageUrl' => 'foo://bar',
            'caption' => 'foo caption',
        ]);

        $this->assertSame(
            [
                MetaData::VALUE_ALT => 'foo alt',
                MetaData::VALUE_CAPTION => 'foo caption',
                MetaData::VALUE_TITLE => 'foo title',
                MetaData::VALUE_URL => 'foo://bar',
            ],
            $model->getOverwriteMetaData()->all()
        );
    }

    public function testDoesNotCreateMetaDataContainerFromContentModelIfOverwriteIsDisabled(): void
    {
        /** @var ContentModel $model */
        $model = (new \ReflectionClass(ContentModel::class))->newInstanceWithoutConstructor();

        $model->setRow([
            'id' => 100,
            'headline' => 'foobar',
            'overwriteMeta' => '',
            'alt' => 'foo alt',
        ]);

        $this->assertNull($model->getOverwriteMetaData());
    }

    public function testCreatesMetaDataContainerFromFilesModel(): void
    {
        /** @var FilesModel $model */
        $model = (new \ReflectionClass(FilesModel::class))->newInstanceWithoutConstructor();

        $model->setRow([
            'id' => 100,
            'name' => 'test',
            'meta' => serialize([
                'de' => [
                    'title' => 'foo title',
                    'alt' => 'foo alt',
                    'caption' => 'foo caption',
                ],
                'en' => [
                    'title' => 'bar title',
                    'alt' => 'bar alt',
                    'link' => 'foo://bar',
                    'caption' => 'bar caption',
                    'custom' => 'foobar',
                ],
            ]),
        ]);

        $this->assertSame(
            [
                MetaData::VALUE_TITLE => 'bar title',
                MetaData::VALUE_ALT => 'bar alt',
                MetaData::VALUE_URL => 'foo://bar',
                MetaData::VALUE_CAPTION => 'bar caption',
                'custom' => 'foobar',
            ],
            $model->getMetaData('en')->all(),
            'get all meta from single locale'
        );

        $this->assertSame(
            [
                MetaData::VALUE_TITLE => 'foo title',
                MetaData::VALUE_ALT => 'foo alt',
                MetaData::VALUE_URL => '',
                MetaData::VALUE_CAPTION => 'foo caption',
            ],
            $model->getMetaData('es', 'de', 'en')->all(),
            'get all meta data of first matching locale'
        );

        $this->assertNull(
            $model->getMetaData('es'),
            'return null if no meta data is available for a locale'
        );
    }
}
