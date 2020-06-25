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

use Contao\ContentModel;
use Contao\CoreBundle\File\MetaData;
use Contao\FilesModel;
use Contao\System;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MetaDataTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        System::setContainer(self::bootKernel()->getContainer());

        $GLOBALS['TL_LANG']['MSC']['deleteConfirmFile'] = '';
    }

    public function testCreatesMetaDataContainerFromContentModel(): void
    {
        /** @var ContentModel $model */
        $model = (new ReflectionClass(ContentModel::class))
            ->newInstanceWithoutConstructor()
        ;

        $model->setRow([
            'id' => 100,
            'headline' => 'foobar',
            'overwriteMeta' => '1',
            'alt' => 'foo alt',
            'imageTitle' => 'foo title',
            'imageUrl' => 'foo://bar',
            'caption' => 'foo caption',
        ]);

        $this->assertSame([
            MetaData::VALUE_ALT => 'foo alt',
            MetaData::VALUE_CAPTION => 'foo caption',
            MetaData::VALUE_TITLE => 'foo title',
            MetaData::VALUE_URL => 'foo://bar',
        ], $model->getOverwriteMetaData()->all());
    }

    public function testDoesNotCreateMetaDataContainerFromContentModelIfOverwriteIsDisabled(): void
    {
        /** @var ContentModel $model */
        $model = (new ReflectionClass(ContentModel::class))
            ->newInstanceWithoutConstructor()
        ;

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
        $model = (new ReflectionClass(FilesModel::class))
            ->newInstanceWithoutConstructor()
        ;

        $model->setRow([
            'id' => 100,
            'name' => 'test',
            'meta' => serialize([
                'de' => [
                    'alt' => 'foo alt',
                    'caption' => 'foo caption',
                    'title' => 'foo title',
                ],
                'en' => [
                    'alt' => 'bar alt',
                    'caption' => 'bar caption',
                    'title' => 'bar title',
                    'link' => 'foo://bar',
                    'custom' => 'foobar',
                ],
            ]),
        ]);

        $this->assertSame(
            [
                MetaData::VALUE_ALT => 'bar alt',
                MetaData::VALUE_CAPTION => 'bar caption',
                MetaData::VALUE_TITLE => 'bar title',
                MetaData::VALUE_URL => 'foo://bar',
                'custom' => 'foobar',
            ],
            $model->getMetaData('en')->all(),
            'get all meta from single locale'
        );

        $this->assertSame(
            [
                MetaData::VALUE_ALT => 'foo alt',
                MetaData::VALUE_CAPTION => 'foo caption',
                MetaData::VALUE_TITLE => 'foo title',
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
