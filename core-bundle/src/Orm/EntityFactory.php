<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Orm;

use Contao\CoreBundle\Exception\GenerateEntityException;
use Contao\CoreBundle\Orm\Attribute\Extension;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\Filesystem\Filesystem;

class EntityFactory
{
    public function generateEntityClasses(string $directory, array $entities, array $extensions): void
    {
        if (0 === \count($entities)) {
            return;
        }

        $tree = [];

        foreach ($extensions as $extensionClass) {
            try {
                $reflectionClass = new \ReflectionClass($extensionClass);
            } catch (\ReflectionException $e) {
                continue;
            }

            $attributes = $reflectionClass->getAttributes(Extension::class);

            if (\count($attributes) > 1) {
                throw new GenerateEntityException('Extension of more than one entity is not supported');
            }

            if (0 === \count($attributes)) {
                continue;
            }

            $attribute = $attributes[0];
            $arguments = $attribute->getArguments();

            /** @var string $index */
            $index = $arguments[0];

            if (!\in_array($index, $entities, true)) {
                continue;
            }

            if (!\array_key_exists($index, $tree)) {
                $tree[$index] = [];
            }

            $tree[$index][] = $extensionClass;
        }

        foreach ($tree as $entity => $extensions) {
            try {
                $reflectionClass = new \ReflectionClass($entity);
            } catch (\ReflectionException $e) {
                continue;
            }

            $class = new ClassType($reflectionClass->getShortName());
            $class->setExtends($entity);
            $class->setComment('This entity is auto generated.');
            $class->setAbstract(false);
            $class->setFinal(true);


            // Set class attributes
            $attributes = $reflectionClass->getAttributes();

            foreach ($attributes as $attribute) {
                $class->addAttribute($attribute->getName(), $attribute->getArguments());
            }

            foreach ($extensions as $extension) {
                $class->addTrait($extension);

                // Check if extensions has other attributes than Extension
                // If so, attach to class as attribute
                try {
                    $reflectionExtension = new \ReflectionClass($extension);
                } catch (\ReflectionException $e) {
                    continue;
                }

                $attributes = $reflectionExtension->getAttributes();

                foreach ($attributes as $attribute) {
                    if (Extension::class === $attribute->getName()) {
                        continue;
                    }

                    $class->addAttribute($attribute->getName(), $attribute->getArguments());
                }
            }

            $printer = new CodePrinter();

            $namespace = new PhpNamespace('GeneratedEntity');


            $file = new PhpFile();
            $file->setStrictTypes(true);
            $file->addComment('This file is auto generated');
            $file->addNamespace($namespace);

            $generated = sprintf("%s\n\n%s", $printer->printFile($file), $printer->printClass($class, $namespace));

            $filesystem = new Filesystem();
            $filesystem->dumpFile(sprintf('%s/%s.php', $directory, $reflectionClass->getShortName()), $generated);
        }
    }
}
