<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cache;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\MappingException;

class EntityTagger
{
    /**
     * @var array<string, ClassMetadata>
     */
    private array $classMetadata = [];
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param iterable|string|object|null $target
     *
     * @return array<int, string>
     */
    public function getTags($target): array
    {
        if (!$target) {
            return [];
        }

        if (\is_string($target)) {
            if (preg_match('/^(?:[a-z_\x80-\xff][a-z0-9_\x80-\xff]*\\\\?)+(?<!\\\\)$/i', $target)) {
                try {
                    return [$this->getTagForEntityClass($target)];
                } catch (\InvalidArgumentException $e) {
                    // ignore
                }
            }

            return [$target];
        }

        if (is_iterable($target)) {
            $tags = [];

            foreach ($target as $part) {
                $tags = [...$tags, ...$this->getTags($part)];
            }

            return array_unique($tags);
        }

        return [$this->getTagForEntityInstance($target)];
    }

    public function getTagForEntityClass(string $className): string
    {
        $metadata = $this->getClassMetadata($className);

        if (null === $metadata) {
            throw new \InvalidArgumentException(sprintf('The given class name "%s" is no valid entity class.', $className));
        }

        return sprintf('contao.db.%s', $metadata->getTableName());
    }

    public function getTagForEntityInstance(object $object): string
    {
        $metadata = $this->getClassMetadata(\get_class($object));

        if (null === $metadata) {
            throw new \InvalidArgumentException(sprintf('The given object of type "%s" is no valid entity instance.', \get_class($object)));
        }

        $identifier = $this->entityManager
            ->getUnitOfWork()
            ->getSingleIdentifierValue($object)
        ;

        return sprintf('contao.db.%s.%s', $metadata->getTableName(), $identifier);
    }

    private function getClassMetadata(string $className): ?ClassMetadata
    {
        $getMetadata = function (string $className) {
            try {
                return $this->entityManager->getClassMetadata($className);
            } catch (MappingException $e) {
                return null;
            }
        };

        $this->classMetadata[$className] ??= $getMetadata($className);

        return $this->classMetadata[$className];
    }
}
