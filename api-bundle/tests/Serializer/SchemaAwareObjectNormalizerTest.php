<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Serializer;

use Contao\ApiBundle\Serializer\SchemaAwareObjectNormalizer;
use Contao\ApiBundle\Serializer\VirtualFilesystemMetadataNormalizationHandler;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\File\TextTrack;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\Image\ImportantPart;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

final class SchemaAwareObjectNormalizerTest extends TestCase
{
    public function testSerializesAndDeserializesObjects(): void
    {
        $data = [
            'localized' => ['en' => ['title' => 'Example']],
            'importantPart' => ['x' => 0.1, 'y' => 0.2, 'width' => 0.3, 'height' => 0.4],
            'textTrack' => ['sourceLanguage' => 'en', 'type' => 'subtitles'],
            'custom' => ['enabled' => true],
        ];
        $normalizer = new SchemaAwareObjectNormalizer(new Validator(), [new VirtualFilesystemMetadataNormalizationHandler()]);
        $metadata = $normalizer->fromArray(ExtraMetadata::class, $data);

        $this->assertInstanceOf(ExtraMetadata::class, $metadata);
        $this->assertInstanceOf(MetadataBag::class, $metadata->getLocalized());
        $this->assertInstanceOf(ImportantPart::class, $metadata->getImportantPart());
        $this->assertInstanceOf(TextTrack::class, $metadata->getTextTrack());
        $this->assertSame($data, $normalizer->toArray($metadata));
        $this->assertSame('object', $normalizer->getJsonSchema(ExtraMetadata::class)['properties']['localized']['type']);
    }

    public function testOmitsUnsupportedNestedObjects(): void
    {
        $normalizer = new SchemaAwareObjectNormalizer(new Validator(), [new VirtualFilesystemMetadataNormalizationHandler()]);
        $metadata = new ExtraMetadata(['supported' => 'value', 'unsupported' => new \stdClass()]);

        $this->assertSame(['supported' => 'value'], $normalizer->toArray($metadata));
    }
}
