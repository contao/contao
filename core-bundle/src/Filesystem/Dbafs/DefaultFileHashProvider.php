<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs;

use League\Flysystem\Filesystem;

class DefaultFileHashProvider implements FileHashProviderInterface
{
    /** @var Filesystem */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function getHashes(array $paths): array
    {
        $hashes = [];

        foreach ($paths as $path) {
            if (!$this->filesystem->has($path)) {
                continue;
            }

            $metadata = $this->filesystem->getMetadata($path);
            if ('file' !== $metadata['type']) {
                $hashes[$path] = null;
                continue;
            }

            $content = $this->filesystem->read($path);
            if (false === $content) {
                throw new \RuntimeException("Error hashing resource '$path': Could not read file content.");
            }

            $hashes[$path] = md5($content);
        }

        return $hashes;
    }
}
