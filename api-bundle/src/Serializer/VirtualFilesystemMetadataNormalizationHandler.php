<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Serializer;

use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\File\TextTrack;
use Contao\CoreBundle\File\TextTrackType;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\Image\ImportantPart;
use Symfony\Component\Uid\Uuid;

/**
 * Defines the API representation of VFS metadata without adding serialization
 * concerns to the filesystem value objects in the CoreBundle.
 *
 * ExtraMetadata is an open container whose known entries contain further value
 * objects. This handler recursively converts those objects while preserving
 * custom scalar and array entries supplied by filesystem adapters.
 */
final readonly class VirtualFilesystemMetadataNormalizationHandler implements SchemaAwareNormalizationHandlerInterface
{
    /**
     * These are the only object types that may occur in the documented VFS metadata
     * representation. Unknown objects cannot safely be exposed.
     */
    private const SUPPORTED_TYPES = [
        ExtraMetadata::class,
        Metadata::class,
        MetadataBag::class,
        TextTrack::class,
        ImportantPart::class,
    ];

    /**
     * The normalizer calls this with an object while producing a response and with a
     * class name while processing request data or generating a schema.
     */
    public function supports(object|string $value): bool
    {
        return \in_array(\is_object($value) ? $value::class : $value, self::SUPPORTED_TYPES, true);
    }

    /**
     * Uses the public value-object APIs instead of reflecting private state. This
     * keeps the API representation independent of implementation details.
     */
    public function toArray(object $value): array
    {
        return match (true) {
            $value instanceof ExtraMetadata => $this->normalizeExtraMetadata($value),
            $value instanceof Metadata => $value->all(),
            $value instanceof MetadataBag => array_map($this->toArray(...), $value->all()),
            $value instanceof TextTrack => ['sourceLanguage' => $value->getSourceLanguage(), 'type' => $value->getType()?->value],
            $value instanceof ImportantPart => ['x' => $value->getX(), 'y' => $value->getY(), 'width' => $value->getWidth(), 'height' => $value->getHeight()],
            default => throw new \InvalidArgumentException(\sprintf('The object "%s" is not supported.', $value::class)),
        };
    }

    /**
     * Input has already been checked against getJsonSchema() by the delegating
     * normalizer, so this method only reconstructs the domain representation.
     */
    public function fromArray(string $class, array $data): object
    {
        return match ($class) {
            ExtraMetadata::class => $this->denormalizeExtraMetadata($data),
            Metadata::class => $this->denormalizeMetadata($data),
            MetadataBag::class => new MetadataBag(array_map($this->denormalizeMetadata(...), $data)),
            TextTrack::class => new TextTrack($data['sourceLanguage'], null === $data['type'] ? null : TextTrackType::from($data['type'])),
            ImportantPart::class => new ImportantPart(...$data),
            default => throw new \InvalidArgumentException(\sprintf('The class "%s" is not supported.', $class)),
        };
    }

    /**
     * Keeps every supported object shape next to its bidirectional conversion.
     */
    public function getJsonSchema(string $class): array
    {
        return match ($class) {
            ExtraMetadata::class => $this->getExtraMetadataSchema(),
            Metadata::class => $this->getMetadataSchema(),
            MetadataBag::class => ['type' => 'object', 'additionalProperties' => $this->getJsonSchema(Metadata::class)],
            TextTrack::class => $this->getTextTrackSchema(),
            ImportantPart::class => $this->getImportantPartSchema(),
            default => throw new \InvalidArgumentException(\sprintf('No JSON Schema is registered for "%s".', $class)),
        };
    }

    private function normalizeExtraMetadata(ExtraMetadata $metadata): array
    {
        $data = [];

        foreach ($metadata->all() as $key => $value) {
            // The file UUID is stored as an object internally but represented as a read-only
            // RFC 4122 string alongside the remaining metadata.
            if ('uuid' === $key && $value instanceof Uuid) {
                $data[$key] = $value->toRfc4122();

                continue;
            }

            // ExtraMetadata can be extended by filesystem adapters. Preserve portable values
            // but do not guess how an unknown object is shaped.
            if ($this->isNormalizable($value)) {
                $data[$key] = $this->normalizeValue($value);
            }
        }

        return $data;
    }

    private function denormalizeExtraMetadata(array $data): ExtraMetadata
    {
        if (\array_key_exists('uuid', $data)) {
            throw new \InvalidArgumentException('The UUID cannot be changed.');
        }

        // Only well-known entries need reconstruction. Custom entries remain the scalar
        // or array values accepted by the open ExtraMetadata container.
        foreach (['localized' => MetadataBag::class, 'textTrack' => TextTrack::class, 'importantPart' => ImportantPart::class] as $key => $class) {
            if (\is_array($data[$key] ?? null)) {
                $data[$key] = $this->fromArray($class, $data[$key]);
            }
        }

        return new ExtraMetadata($data);
    }

    private function denormalizeMetadata(array $data): Metadata
    {
        // DBAFS injects the file UUID into every localized metadata object. It
        // identifies the owning file and is never client-controlled metadata.
        if (\array_key_exists(Metadata::VALUE_UUID, $data)) {
            throw new \InvalidArgumentException('The localized metadata UUID cannot be changed.');
        }

        return new Metadata($data);
    }

    private function isNormalizable(mixed $value): bool
    {
        if (\is_object($value)) {
            return $this->supports($value);
        }

        return null === $value || \is_scalar($value) || (\is_array($value) && array_all($value, fn (mixed $item): bool => $this->isNormalizable($item)));
    }

    private function normalizeValue(mixed $value): mixed
    {
        // Recursion is needed for custom metadata arrays that contain any of the
        // supported VFS value objects below their first level.
        if (\is_object($value)) {
            return $this->toArray($value);
        }

        return \is_array($value) ? array_map($this->normalizeValue(...), $value) : $value;
    }

    private function getExtraMetadataSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'uuid' => ['type' => 'string', 'format' => 'uuid', 'readOnly' => true],
                'localized' => $this->getJsonSchema(MetadataBag::class),
                'textTrack' => $this->getJsonSchema(TextTrack::class),
                'importantPart' => $this->getJsonSchema(ImportantPart::class),
            ],
            // Filesystem adapters may add portable metadata beyond the known DBAFS entries,
            // which must remain available through the API.
            'additionalProperties' => true,
        ];
    }

    private function getMetadataSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                Metadata::VALUE_ALT => ['type' => ['string', 'null']],
                Metadata::VALUE_CAPTION => ['type' => ['string', 'null']],
                Metadata::VALUE_TITLE => ['type' => ['string', 'null']],
                Metadata::VALUE_URL => ['type' => ['string', 'null']],
                Metadata::VALUE_UUID => ['type' => ['string', 'null'], 'format' => 'uuid', 'readOnly' => true],
                Metadata::VALUE_LICENSE => ['type' => ['string', 'null']],
            ],
            'additionalProperties' => true,
        ];
    }

    private function getTextTrackSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'sourceLanguage' => ['type' => 'string'],
                'type' => ['type' => ['string', 'null'], 'enum' => [...array_column(TextTrackType::cases(), 'value'), null]],
            ],
            'required' => ['sourceLanguage', 'type'],
            'additionalProperties' => false,
        ];
    }

    private function getImportantPartSchema(): array
    {
        $coordinate = ['type' => 'number', 'minimum' => 0, 'maximum' => 1];

        return [
            'type' => 'object',
            'properties' => array_fill_keys(['x', 'y', 'width', 'height'], $coordinate),
            'required' => ['x', 'y', 'width', 'height'],
            'additionalProperties' => false,
        ];
    }
}
