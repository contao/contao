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
 * Identifies the VFS containing related files without depending on its
 * storage implementation.
 */
final readonly class VirtualFilesystemRelationDefinition
{
    public function __construct(public string $filesystem)
    {
        if ('' === $this->filesystem) {
            throw new \InvalidArgumentException('The relation filesystem must not be empty.');
        }
    }
}
