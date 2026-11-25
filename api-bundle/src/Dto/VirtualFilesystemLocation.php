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

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

final readonly class VirtualFilesystemLocation
{
    public const array SCHEMA = [
        'type' => 'object',
        'properties' => [
            'path' => ['type' => 'string', 'minLength' => 1],
            'uuid' => ['type' => 'string', 'format' => 'uuid'],
        ],
        'oneOf' => [['required' => ['path']], ['required' => ['uuid']]],
        'additionalProperties' => false,
    ];

    public function __construct(
        public string|null $path = null,
        public string|null $uuid = null,
    ) {
        if ((null === $path) === (null === $uuid) || '' === $path || (null !== $uuid && !Uuid::isValid($uuid))) {
            throw new BadRequestHttpException('Specify exactly one non-empty path or valid UUID.');
        }
    }

    public static function fromArray(array $value): self
    {
        if (array_diff_key($value, ['path' => true, 'uuid' => true]) || array_any($value, static fn ($part): bool => !\is_string($part))) {
            throw new BadRequestHttpException('Specify exactly one non-empty path or valid UUID.');
        }

        return new self($value['path'] ?? null, $value['uuid'] ?? null);
    }

    public function toLocation(): Uuid|string
    {
        return $this->path ?? Uuid::fromString($this->uuid ?? '');
    }
}
