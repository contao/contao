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

use Contao\DataContainer;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class DataContainerMove
{
    public const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'target' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Parent ID for first/last, sibling ID for after. Zero denotes the root.'],
            'position' => ['type' => 'string', 'enum' => ['first', 'last', 'after'], 'default' => 'last'],
            'ptable' => ['type' => 'string', 'description' => 'Destination parent table for data containers supporting dynamic parents.'],
        ],
        'required' => ['target'],
        'additionalProperties' => false,
    ];

    public function __construct(
        public readonly int $target,
        public readonly string $position = 'last',
        public readonly string|null $ptable = null,
    ) {
        if ($target < 0 || !\in_array($position, ['first', 'last', 'after'], true) || ('after' === $position && 0 === $target) || '' === $ptable) {
            throw new UnprocessableEntityHttpException('Invalid move destination or position.');
        }
    }

    public function getParameters(): array
    {
        $parameters = ['pid' => $this->target, 'mode' => match ($this->position) {
            'first' => DataContainer::PASTE_INTO,
            'last' => DataContainer::PASTE_INTO_APPEND,
            'after' => DataContainer::PASTE_AFTER,
            default => throw new UnprocessableEntityHttpException('Invalid move position.'),
        }];

        if (null !== $this->ptable) {
            $parameters['ptable'] = $this->ptable;
        }

        return $parameters;
    }
}
