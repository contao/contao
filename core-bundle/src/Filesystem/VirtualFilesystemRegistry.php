<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

use Psr\Container\ContainerInterface;

/**
 * @experimental
 */
class VirtualFilesystemRegistry
{
    /**
     * @internal
     *
     * @param array<string, array<string, mixed>> $tags
     */
    public function __construct(
        private readonly ContainerInterface $filesystems,
        private readonly array $tags,
    ) {
    }

    public function get(string $identifier): VirtualFilesystemInterface
    {
        if (!$this->has($identifier)) {
            throw new \InvalidArgumentException(\sprintf('A virtual filesystem with the name "%s" does not exist.', $identifier));
        }

        return $this->filesystems->get($identifier);
    }

    public function has(string $identifier): bool
    {
        return isset($this->tags[$identifier]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTags(string $identifier): array
    {
        if (!$this->has($identifier)) {
            throw new \InvalidArgumentException(\sprintf('A virtual filesystem with the name "%s" does not exist.', $identifier));
        }

        return $this->tags[$identifier];
    }

    /**
     * Returns all metadata for filesystems matching every criterion using strict equality.
     *
     * @param array<string, mixed> $criteria
     *
     * @return array<string, array<string, mixed>>
     */
    public function findByTags(array $criteria = []): array
    {
        return array_filter(
            $this->tags,
            static function (array $tags) use ($criteria): bool {
                foreach ($criteria as $name => $value) {
                    if (!\array_key_exists($name, $tags) || $tags[$name] !== $value) {
                        return false;
                    }
                }

                return true;
            },
        );
    }
}
