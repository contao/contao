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

final class GroupedDocumentIds
{
    /*
     * @param array<string, array<string>> $typeToIds
     */
    public function __construct(private array $typeToIds = [])
    {
        foreach ($this->typeToIds as $type => $ids) {
            if (!\is_string($type) || !\is_array($ids) || array_filter($ids, 'is_string') !== $ids) {
                throw new \InvalidArgumentException('Invalid input: Keys must be strings and values must be arrays of strings.');
            }
        }
    }

    public function isEmpty(): bool
    {
        return [] === $this->typeToIds;
    }

    public function hasType(string $type): bool
    {
        return isset($this->typeToIds[$type]);
    }

    public function has(string $type, string $documentId): bool
    {
        return $this->hasType($type) && \in_array($documentId, $this->typeToIds[$type], true);
    }

    /**
     * @return array<string> the list of document IDs for the given type
     */
    public function getDocumentIdsForType(string $type): array
    {
        return $this->typeToIds[$type] ?? [];
    }

    public function getTypes(): array
    {
        return array_keys($this->typeToIds);
    }

    public function addIdToType(string $type, string $id): self
    {
        $this->typeToIds[$type] ??= [];

        if (!\in_array($id, $this->typeToIds[$type], true)) {
            $this->typeToIds[$type][] = $id;
        }

        return $this;
    }

    public function removeIdFromType(string $type, string $id): self
    {
        if (!isset($this->typeToIds[$type])) {
            return $this;
        }

        $this->typeToIds[$type] = array_values(
            array_filter($this->typeToIds[$type], static fn ($existingId) => $existingId !== $id),
        );

        if (empty($this->typeToIds[$type])) {
            unset($this->typeToIds[$type]);
        }

        return $this;
    }

    public function toArray(): array
    {
        return $this->typeToIds;
    }

    /**
     * Splits the current instance into an array of GroupedDocumentIds instances, each
     * containing no more than $maxBytes worth of data.
     *
     * @param int $maxBytes Maximum size of each chunk in bytes
     *
     * @return array<GroupedDocumentIds>
     */
    public function split(int $maxBytes): array
    {
        // No IDs provided at all, make sure we return ourselves as a chunk
        if ([] === $this->typeToIds) {
            return [$this];
        }

        $chunks = [];
        $currentChunk = [];
        $currentSize = 0;

        foreach ($this->typeToIds as $type => $ids) {
            foreach ($ids as $id) {
                $entrySize = \strlen($type) + \strlen($id);

                if ($currentSize + $entrySize > $maxBytes) {
                    $chunks[] = new self($currentChunk);
                    $currentChunk = [];
                    $currentSize = 0;
                }

                $currentChunk[$type][] = $id;
                $currentSize += $entrySize;
            }
        }

        if ([] !== $currentChunk) {
            $chunks[] = new self($currentChunk);
        }

        return $chunks;
    }

    public static function fromArray(array $typeToIds): self
    {
        return new self($typeToIds);
    }
}
