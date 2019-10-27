<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\ORM\ExtendableEntity;

use Doctrine\ORM\EntityNotFoundException;

class ExtensionRegistry
{
    /** @var array */
    private $extensionMapping = [];

    public function setExtensions(array $extensions): void
    {
        foreach ($extensions as $name => $class) {
            $targetClass = $this->getSharedParentClass($class);

            if (!\array_key_exists($targetClass, $this->extensionMapping)) {
                $this->extensionMapping[$targetClass] = [];
            }

            $this->extensionMapping[$targetClass][$name] = $class;
        }
    }

    public function getExtensions(string $className): array
    {
        return $this->extensionMapping[$className] ?? [];
    }

    /**
     * Find parent class that implements ExtendableEntity.
     */
    private function getSharedParentClass(string $className): string
    {
        $class = new \ReflectionClass($className);

        do {
            $parentClass = $class->getParentClass();

            if (false === $parentClass) {
                throw new EntityNotFoundException(
                    sprintf('%s must extend from a class implementing %s.', $className, ExtendableEntity::class)
                );
            }
        } while (!$parentClass->implementsInterface(ExtendableEntity::class));

        return $parentClass->getName();
    }
}
