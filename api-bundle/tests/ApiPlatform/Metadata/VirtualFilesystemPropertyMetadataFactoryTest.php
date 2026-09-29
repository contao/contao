<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Contao\ApiBundle\ApiPlatform\Metadata\VirtualFilesystemPropertyMetadataFactory;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Serializer\SchemaAwareObjectNormalizer;
use Contao\ApiBundle\Serializer\VirtualFilesystemMetadataNormalizationHandler;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

final class VirtualFilesystemPropertyMetadataFactoryTest extends TestCase
{
    public function testAddsTheMetadataSchema(): void
    {
        $decorated = $this->createStub(PropertyMetadataFactoryInterface::class);
        $decorated
            ->method('create')
            ->willReturn(new ApiProperty())
        ;

        $normalizer = new SchemaAwareObjectNormalizer(new Validator(), [new VirtualFilesystemMetadataNormalizationHandler()]);

        $factory = new VirtualFilesystemPropertyMetadataFactory($decorated, $normalizer);
        $metadata = $factory->create(VirtualFilesystemItem::class, 'metadata');

        $this->assertSame($normalizer->getJsonSchema(ExtraMetadata::class), $metadata->getJsonSchemaContext());
        $this->assertSame($normalizer->getJsonSchema(ExtraMetadata::class), $metadata->getOpenapiContext());
    }
}
