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

use Opis\JsonSchema\Validator;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * Normalizes domain objects without requiring them to implement an API-specific
 * interface or changing how Symfony serializes the surrounding resource DTO.
 *
 * Each handler owns the array representation and JSON Schema for its supported
 * objects. Keeping both in the same handler prevents the documented API shape,
 * input validation and object construction from drifting apart.
 *
 * This service is called explicitly at API boundaries. It is deliberately not
 * registered as a Symfony serializer normalizer that would be considered for
 * every object processed by API Platform.
 */
final readonly class SchemaAwareObjectNormalizer
{
    /**
     * @param iterable<SchemaAwareNormalizationHandlerInterface> $handlers
     */
    public function __construct(
        private Validator $validator,
        private iterable $handlers,
    ) {
    }

    /**
     * Converts an object to the structured data exposed through the API.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $value): array
    {
        return $this->getHandler($value)->toArray($value);
    }

    /**
     * Validates structured API data before reconstructing the requested object.
     *
     * @param class-string         $class
     * @param array<string, mixed> $data
     */
    public function fromArray(string $class, array $data): object
    {
        $this->validate($class, $data);

        return $this->getHandler($class)->fromArray($class, $data);
    }

    /**
     * Returns the same schema used for input validation so callers can also use it
     * when describing the normalized value in OpenAPI.
     *
     * @param class-string $class
     *
     * @return array<string, mixed>
     */
    public function getJsonSchema(string $class): array
    {
        return $this->getHandler($class)->getJsonSchema($class);
    }

    private function getHandler(object|string $value): SchemaAwareNormalizationHandlerInterface
    {
        // Handlers are tagged services so another domain can add support without
        // extending this service or adding a central class-to-handler map.
        foreach ($this->handlers as $handler) {
            if ($handler->supports($value)) {
                return $handler;
            }
        }

        $class = \is_object($value) ? $value::class : $value;

        throw new \InvalidArgumentException(\sprintf('No object normalization handler supports "%s".', $class));
    }

    private function validate(string $class, array $data): void
    {
        // Opis validates decoded JSON values. Round-tripping the schema changes
        // associative PHP arrays into the JSON objects the validator expects.
        $schema = json_decode(json_encode($this->getJsonSchema($class), JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $result = $this->validator->validate($this->toJsonValue($data, true), $schema);

        if (!$result->isValid()) {
            throw new NotNormalizableValueException((string) $result);
        }
    }

    private function toJsonValue(mixed $value, bool $forceObject = false): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        // PHP represents JSON arrays and objects with the same array type. Lists must
        // stay arrays while maps must become objects for JSON Schema types.
        if (!$forceObject && array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->toJsonValue($item), $value);
        }

        $object = new \stdClass();

        foreach ($value as $key => $item) {
            $object->{$key} = $this->toJsonValue($item);
        }

        return $object;
    }
}
