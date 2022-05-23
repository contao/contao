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
use Contao\CoreBundle\Exception\GenerateEntityException;

class EntityExtensionCollector
{
    private array $paths;
    private ?array $cached = null;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    public function collect(): array
    {
        if (null !== $this->cached) {
            return $this->cached;
        }

        $extensions = [];
        $includedFiles = [];

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+'.preg_quote('.php').'$/i',
                \RecursiveRegexIterator::GET_MATCH
            );

            foreach ($iterator as $file) {
                $sourceFile = $file[0];

                require_once $sourceFile;

                $includedFiles[] = $sourceFile;
            }
        }

        $declared = get_declared_traits();

        foreach ($declared as $traitName) {
            $rc = new \ReflectionClass($traitName);
            $sourceFile = $rc->getFileName();

            if (!\in_array($sourceFile, $includedFiles, true)) {
                continue;
            }

            $extensions[] = $traitName;
        }

        $this->cached = $extensions;

        return $this->generateExtensionConfigStruct($extensions);
    }

    private function generateExtensionConfigStruct(array $extensions): array
    {
        $struct = [];

        foreach ($extensions as $extension) {
            try {
                $trait = new \ReflectionClass($extension);
            } catch (\ReflectionException $e) {
                continue;
            }

            $attributes = $trait->getAttributes(EntityExtension::class);

            if (\count($attributes) > 1) {
                throw new GenerateEntityException('Extension of more than one entity is not supported');
            }

            if (0 === \count($attributes)) {
                continue;
            }

            $attribute = new EntityExtension(...$attributes[0]->getArguments());

            // TODO: Convert to CamelCase
            $className = $attribute->entity;

            if (!\array_key_exists($className, $struct)) {
                $struct[$className] = [];
            }

            $struct[$className][] = $extension;
        }

        return $struct;
    }
}
