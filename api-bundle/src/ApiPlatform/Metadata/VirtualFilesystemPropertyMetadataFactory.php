<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Serializer\SchemaAwareObjectNormalizer;
use Contao\CoreBundle\Filesystem\ExtraMetadata;

final readonly class VirtualFilesystemPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(
        private PropertyMetadataFactoryInterface $decorated,
        private SchemaAwareObjectNormalizer $objectNormalizer,
    ) {
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $metadata = $this->decorated->create($resourceClass, $property, $options);

        if (VirtualFilesystemItem::class !== $resourceClass || 'metadata' !== $property) {
            return $metadata;
        }

        $schema = $this->objectNormalizer->getJsonSchema(ExtraMetadata::class);

        return $metadata
            ->withJsonSchemaContext($schema)
            ->withOpenapiContext($schema)
        ;
    }
}
