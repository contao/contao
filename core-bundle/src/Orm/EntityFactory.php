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

            $index = $arguments['entity'];

            if (!\in_array($index, $entities, true)) {
                continue;
            }

            if (!\array_key_exists($index, $tree)) {
                $tree[$index] = [
                    'extensions' => [],
                    'indexes' => [],
                ];
            }

            $tree[$index]['extensions'][] = $extensionClass;
            $tree[$index]['indexes'] = array_merge($tree[$index]['indexes'], $arguments['indexes']);
        }

        foreach ($tree as $entity => $config) {
            $extensions = $config['extensions'];
            // $indexes = $config['indexes'];

            try {
                $reflectionClass = new \ReflectionClass($entity);
            } catch (\ReflectionException $e) {
                continue;
            }

            /** @var ClassType $class */
            $class = ClassType::from($entity, true);

            foreach ($extensions as $extension) {
                $class->addTrait($extension);
            }

            $comment = 'This entity is auto generated.';

            $class->setComment($comment);
            $class->setAbstract(false);
            $class->setExtends($entity);

            $printer = new CodePrinter();
            $generated = $printer->printClass($class, new PhpNamespace('Contao\CoreBundle\GeneratedEntity'));

            $filesystem = new Filesystem();
            $filesystem->dumpFile(sprintf('%s/%s.php', $directory, $reflectionClass->getShortName()), $generated);
        }
    }
}
