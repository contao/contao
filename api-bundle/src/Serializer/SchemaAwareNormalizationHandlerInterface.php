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

/**
 * Defines a schema-backed, bidirectional representation for a group of related
 * domain objects. Implementations are discovered by SchemaAwareObjectNormalizer.
 */
interface SchemaAwareNormalizationHandlerInterface
{
    /**
     * Whether this handler can normalize the object or denormalize the class.
     */
    public function supports(object|string $value): bool;

    /**
     * Converts a supported object to JSON-compatible structured data.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $value): array;

    /**
     * Reconstructs a supported object from already validated data.
     *
     * @param class-string         $class
     * @param array<string, mixed> $data
     */
    public function fromArray(string $class, array $data): object;

    /**
     * Describes the exact structured representation used in both directions.
     *
     * @param class-string $class
     *
     * @return array<string, mixed>
     */
    public function getJsonSchema(string $class): array;
}
