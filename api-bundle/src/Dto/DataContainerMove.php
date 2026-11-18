<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Dto;

use Contao\ApiBundle\Exception\InvalidInputException;
use Contao\DataContainer;

final class DataContainerMove
{
    public const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'target' => [
                'oneOf' => [['type' => 'integer', 'minimum' => 0], ['type' => 'string', 'format' => 'iri-reference']],
                'description' => 'The destination: the parent for first/last, the sibling for after. Zero denotes the root. An ID is a record of ptable if sent, otherwise of the default parent table of the data container (tl_article for content elements of an article); it is never looked up in other tables. An IRI (the "@id" of the destination) names its own table, e.g. an element group.',
            ],
            'position' => ['type' => 'string', 'enum' => ['first', 'last', 'after'], 'default' => 'last'],
            'ptable' => ['type' => 'string', 'description' => 'Table of the destination parent for data containers with dynamic parents, e.g. tl_content to move a content element into an element group. Without ptable or an IRI target, the destination is a record of the default parent table.'],
        ],
        'required' => ['target'],
        'additionalProperties' => false,
    ];

    public function __construct(
        public readonly int|string $target,
        public readonly string $position = 'last',
        public readonly string|null $ptable = null,
    ) {
        if ((\is_int($target) && $target < 0) || '' === $target || !\in_array($position, ['first', 'last', 'after'], true) || ('after' === $position && 0 === $target) || '' === $ptable) {
            throw new InvalidInputException('Invalid move destination or position.');
        }
    }

    public function getParameters(): array
    {
        $parameters = [
            'pid' => $this->target,
            'mode' => match ($this->position) {
                'first' => DataContainer::PASTE_INTO,
                'last' => DataContainer::PASTE_INTO_APPEND,
                'after' => DataContainer::PASTE_AFTER,
                default => throw new InvalidInputException('Invalid move position.'),
            },
        ];

        if (null !== $this->ptable) {
            $parameters['ptable'] = $this->ptable;
        }

        return $parameters;
    }
}
