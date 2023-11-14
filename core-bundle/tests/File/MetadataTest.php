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

use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaLoader;
use Contao\FilesModel;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class MetadataTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.insert_tag.parser', new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class)));

        System::setContainer($container);

        $GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields'] = [
            'title' => '', 'alt' => '', 'link' => '', 'caption' => '',
        ];
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_LANG'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([DcaLoader::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testCreateAndAccessMetadataContainer(): void
    {
        $metadata = new Metadata([
            Metadata::VALUE_ALT => 'alt',
            Metadata::VALUE_CAPTION => 'caption',
            Metadata::VALUE_TITLE => 'title',
            Metadata::VALUE_URL => 'url',
            Metadata::VALUE_UUID => '1234-5678',
            Metadata::VALUE_LICENSE => 'https://creativecommons.org/licenses/by/4.0/',
            'foo' => 'bar',
        ]);

        $this->assertFalse($metadata->empty());

        $this->assertSame('alt', $metadata->getAlt());
        $this->assertSame('caption', $metadata->getCaption());
        $this->assertSame('title', $metadata->getTitle());
        $this->assertSame('url', $metadata->getUrl());
        $this->assertSame('https://creativecommons.org/licenses/by/4.0/', $metadata->getLicense());
        $this->assertSame('bar', $metadata->get('foo'));

        $this->assertSame(
            [
                Metadata::VALUE_ALT => 'alt',
                Metadata::VALUE_CAPTION => 'caption',
                Metadata::VALUE_TITLE => 'title',
                Metadata::VALUE_URL => 'url',
                Metadata::VALUE_UUID => '1234-5678',
                Metadata::VALUE_LICENSE => 'https://creativecommons.org/licenses/by/4.0/',
                'foo' => 'bar',
            ],
            $metadata->all(),
        );
    }

    public function testGetEmpty(): void
    {
        $metadata = new Metadata([]);

        $this->assertSame('', $metadata->getAlt());
        $this->assertSame('', $metadata->getCaption());
        $this->assertSame('', $metadata->getTitle());
        $this->assertSame('', $metadata->getUrl());
        $this->assertSame('', $metadata->getLicense());

        $this->assertNull($metadata->get('foo'));
    }

    public function testEmpty(): void
    {
        $metadata = new Metadata([]);

        $this->assertTrue($metadata->empty());
    }

    public function testHas(): void
    {
        $metadata = new Metadata([
            Metadata::VALUE_ALT => '',
            'foo' => 'bar',
        ]);

        $this->assertTrue($metadata->has(Metadata::VALUE_ALT));
        $this->assertTrue($metadata->has('foo'));
        $this->assertFalse($metadata->has('bar'));
    }

    public function testCreatesMetadataContainerFromContentModel(): void
    {
        $model = $this->mockClassWithProperties(ContentModel::class, except: ['getOverwriteMetadata']);

        $model->setRow([
            'id' => 100,
            'headline' => 'foobar',
            'overwriteMeta' => true,
            'alt' => 'foo alt',
            'imageTitle' => 'foo title',
            'imageUrl' => 'foo://bar',
            'caption' => 'foo caption',
        ]);

        $this->assertSame(
            [
                Metadata::VALUE_ALT => 'foo alt',
                Metadata::VALUE_CAPTION => 'foo caption',
                Metadata::VALUE_TITLE => 'foo title',
                Metadata::VALUE_URL => 'foo://bar',
            ],
            $model->getOverwriteMetadata()->all(),
        );
    }

    public function testDoesNotCreateMetadataContainerFromContentModelIfOverwriteIsDisabled(): void
    {
        $model = $this->mockClassWithProperties(ContentModel::class, except: ['getOverwriteMetadata']);

        $model->setRow([
            'id' => 100,
            'headline' => 'foobar',
            'overwriteMeta' => false,
            'alt' => 'foo alt',
        ]);

        $this->assertNull($model->getOverwriteMetadata());
    }

    public function testCreatesMetadataContainerFromFilesModel(): void
    {
        $model = $this->mockClassWithProperties(FilesModel::class, except: ['getMetadata']);

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
                Metadata::VALUE_TITLE => 'bar title',
                Metadata::VALUE_ALT => 'bar alt',
                Metadata::VALUE_URL => 'foo://bar',
                Metadata::VALUE_CAPTION => 'bar caption',
                'custom' => 'foobar',
            ],
            $model->getMetadata('en')->all(),
            'get all meta from single locale',
        );

        $this->assertSame(
            [
                Metadata::VALUE_TITLE => 'foo title',
                Metadata::VALUE_ALT => 'foo alt',
                Metadata::VALUE_URL => '',
                Metadata::VALUE_CAPTION => 'foo caption',
            ],
            $model->getMetadata('es', 'de', 'en')->all(),
            'get all metadata of first matching locale',
        );

        $this->assertNull($model->getMetadata('es'), 'return null if no metadata is available for a locale');
    }

    public function testMergesMetadata(): void
    {
        $metadata = new Metadata(['foo' => 'FOO', 'bar' => 'BAR']);
        $newMetadata = $metadata->with(['foobar' => 'FOOBAR', 'bar' => 'BAZ']);

        $this->assertNotSame($metadata, $newMetadata, 'Should be a different instance.');

        $this->assertSame(
            [
                'foo' => 'FOO',
                'bar' => 'BAZ',
                'foobar' => 'FOOBAR',
            ],
            $newMetadata->all(),
        );
    }

    public function testDoesNotCreateANewInstanceWhenMergingEmptyMetadata(): void
    {
        $metadata = new Metadata(['foo' => 'FOO', 'bar' => 'BAR']);
        $newMetadata = $metadata->with([]);

        $this->assertSame($metadata, $newMetadata, 'Should be the same instance.');
    }

    public function testGettingSchemaOrgData(): void
    {
        $metadata = new Metadata([
            Metadata::VALUE_ALT => 'alt',
            Metadata::VALUE_CAPTION => 'caption',
            Metadata::VALUE_TITLE => 'title',
            Metadata::VALUE_URL => 'url',
            Metadata::VALUE_UUID => '1234-5678',
            Metadata::VALUE_LICENSE => 'https://creativecommons.org/licenses/by/4.0/',
            'foo' => 'bar',
        ]);

        $this->assertSame(
            [
                'AudioObject' => [
                    'name' => 'title',
                    'caption' => 'caption',
                    'license' => 'https://creativecommons.org/licenses/by/4.0/',
                ],
                'ImageObject' => [
                    'name' => 'title',
                    'caption' => 'caption',
                    'license' => 'https://creativecommons.org/licenses/by/4.0/',
                ],
                'MediaObject' => [
                    'name' => 'title',
                    'caption' => 'caption',
                    'license' => 'https://creativecommons.org/licenses/by/4.0/',
                ],
                'VideoObject' => [
                    'name' => 'title',
                    'caption' => 'caption',
                    'license' => 'https://creativecommons.org/licenses/by/4.0/',
                ],
                'DigitalDocument' => [
                    'name' => 'title',
                    'caption' => 'caption',
                    'license' => 'https://creativecommons.org/licenses/by/4.0/',
                ],
                'SpreadsheetDigitalDocument' => [
                    'name' => 'title',
                    'caption' => 'caption',
                    'license' => 'https://creativecommons.org/licenses/by/4.0/',
                ],
            ],
            $metadata->getSchemaOrgData(),
        );

        $this->assertSame(
            [
                'name' => 'title',
                'caption' => 'caption',
                'license' => 'https://creativecommons.org/licenses/by/4.0/',
            ],
            $metadata->getSchemaOrgData('ImageObject'),
        );

        $this->assertSame([], $metadata->getSchemaOrgData('WhateverNonsense'));
    }

    public function testCanCustomizeSchemaOrgData(): void
    {
        $metadata = new Metadata(
            [
                Metadata::VALUE_ALT => 'alt',
            ],
            [
                'ImageObject' => [
                    'name' => 'title',
                    'foobar' => 'baz',
                ],
            ],
        );

        $this->assertSame(
            [
                'name' => 'title',
                'foobar' => 'baz',
            ],
            $metadata->getSchemaOrgData('ImageObject'),
        );
    }
}
