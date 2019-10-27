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

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\DiscriminatorMap;

class DelegatingAnnotationReader implements Reader
{
    /** @var Reader */
    private $reader;

    /** @var ExtensionRegistry */
    private $registry;

    public function __construct(Reader $reader, ExtensionRegistry $registry)
    {
        $this->reader = $reader;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     *
     * Add registered mappings of extending classes to the DiscriminatorMap.
     */
    public function getClassAnnotations(\ReflectionClass $class): array
    {
        $annotations = $this->reader->getClassAnnotations($class);

        if (!$class->implementsInterface(ExtendableEntity::class)) {
            return $annotations;
        }

        $extensions = $this->registry->getExtensions($class->getName());

        if (empty($extensions)) {
            return $annotations;
        }

        // extend the discriminator map
        foreach ($annotations as $annotation) {
            if ($annotation instanceof DiscriminatorMap) {
                $annotation->value = array_merge($annotation->value, $extensions);

                return $annotations;
            }
        }

        // add a discriminator map if not present
        $discriminatorMap = new DiscriminatorMap();
        $discriminatorMap->value = $extensions;

        $annotations[] = $discriminatorMap;

        return $annotations;
    }

    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        // delegate call
        return $this->reader->getClassAnnotation($class, $annotationName);
    }

    public function getMethodAnnotations(\ReflectionMethod $method): array
    {
        // delegate call
        return $this->reader->getMethodAnnotations($method);
    }

    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        // delegate call
        return $this->reader->getMethodAnnotation($method, $annotationName);
    }

    public function getPropertyAnnotations(\ReflectionProperty $property): array
    {
        // delegate call
        return $this->reader->getPropertyAnnotations($property);
    }

    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        // delegate call
        return $this->reader->getPropertyAnnotation($property, $annotationName);
    }
}
