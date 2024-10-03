<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Backend;

/**
 * @experimental
 */
final class Document
{
    /**
     * @var array<string, string>
     */
    private array $tags = [];

    /**
     * @var array<string, string>
     */
    private array $metadata = [];

    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly string $searchableContent,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSearchableContent(): string
    {
        return $this->searchableContent;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string> $tags
     */
    public function withTags(array $tags): self
    {
        $clone = clone $this;
        $clone->tags = $tags;

        return $clone;
    }

    /**
     * Metadata must be JSON encodable, thus, anything not UTF-8 encoded will be
     * stripped automatically.
     *
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = self::recursiveCleanMetadata($metadata);

        return $clone;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
            'searchableContent' => $this->getSearchableContent(),
            'tags' => $this->getTags(),
            'metadata' => $this->getMetadata(),
        ];
    }

    public static function fromArray(array $array): self
    {
        $document = new self(
            $array['id'],
            $array['type'],
            $array['searchableContent'],
        );

        return $document
            ->withTags($array['tags'] ?? [])
            ->withMetadata($array['metadata'] ?? [])
        ;
    }

    private static function recursiveCleanMetadata(array $metadata): array
    {
        $cleanedMetadata = [];

        foreach ($metadata as $key => $value) {
            if (\is_array($value)) {
                $cleanedMetadata[$key] = self::recursiveCleanMetadata($value);
                continue;
            }

            if (!\is_scalar($value)) {
                continue;
            }

            if (\is_string($value)) {
                // Search engines do not support non UTF-8 strings normally
                if (!preg_match('//u', $value)) {
                    continue;
                }
            }

            $cleanedMetadata[$key] = $value;
        }

        return $cleanedMetadata;
    }
}
