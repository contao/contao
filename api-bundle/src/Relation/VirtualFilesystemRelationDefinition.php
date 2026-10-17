<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Relation;

/**
 * Identifies the VFS containing related files without depending on its storage
 * implementation. The identifier selects the value expected by the relation source.
 */
final readonly class VirtualFilesystemRelationDefinition
{
    public const string PATH = 'path';

    public const string UUID = 'uuid';

    public function __construct(
        public string $filesystem,
        public string $identifier,
    ) {
        if (!\in_array($identifier, [self::PATH, self::UUID], true)) {
            throw new \InvalidArgumentException('The relation identifier must be either path or uuid.');
        }

        if ('' === $this->filesystem) {
            throw new \InvalidArgumentException('The relation filesystem must not be empty.');
        }
    }

    public static function forUuid(string $filesystem): self
    {
        return new self($filesystem, self::UUID);
    }

    public static function forPath(string $filesystem): self
    {
        return new self($filesystem, self::PATH);
    }
}
