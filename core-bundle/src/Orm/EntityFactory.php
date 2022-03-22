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

use Contao\CoreBundle\DependencyInjection\Attribute\EntityExtension;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\Filesystem\Filesystem;

class EntityFactory
{
    public function generateEntityClasses(string $directory, array $entities): void
    {
        if (0 === \count($entities)) {
            return;
        }

        foreach ($entities as $entity => $extensions) {
            $class = new ClassType($entity);
            $class->setComment('This entity is auto generated.');
            $class->setAbstract(false);
            $class->setFinal(true);

            $this->makeClassToEntity($class);

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
                    if (EntityExtension::class === $attribute->getName()) {
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
            $filesystem->dumpFile(sprintf('%s/%s.php', $directory, $class->getName()), $generated);
        }
    }

    private function makeClassToEntity(ClassType $class): void
    {
        $class->addAttribute(Entity::class);

        // TODO: Properly create table name
        $class->addAttribute(Table::class, [
            'name' => sprintf('tl_%s', strtolower($class->getName()))
        ]);

        // Create id property
        $class->addProperty('id');

        $id = $class->getProperty('id');
        $id->setPrivate();
        $id->setType('int');
        $id->setNullable(true);
        $id->addAttribute(Id::class);
        $id->addAttribute(Column::class, [
            'type' => 'integer',
            'options' => [
                'unsigned' => true
            ]
        ]);
        $id->addAttribute(GeneratedValue::class);

        // Create id getter
        $class->addMethod('getId');

        $idGetter = $class->getMethod('getId');
        $idGetter->setPublic();
        $idGetter->setReturnType('?int');
        $idGetter->setBody('return $this->id;');
    }
}
