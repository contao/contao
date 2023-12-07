<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Schema;

use Contao\CoreBundle\Dca\Data;
use Contao\CoreBundle\Dca\SchemaFactory;
use Contao\CoreBundle\Dca\Util\Path;

class Schema implements SchemaInterface, SchemaManagerInterface, ParentAwareSchemaInterface
{
    protected SchemaFactory $schemaFactory;

    protected array $schemas = [];

    /**
     * @var array<class-string>
     */
    protected array $schemaClasses = [];

    private SchemaInterface|null $parent = null;

    public function __construct(
        protected string $name,
        protected Data $data,
        SchemaFactory|null $schemaFactory = null,
    ) {
        if ($schemaFactory) {
            $this->schemaFactory = $schemaFactory;
        }
    }

    /**
     * @template T of SchemaInterface
     *
     * @param class-string<T> $className
     *
     * @return T
     */
    public function getSchema(string $key, string|null $className = null, Data|null $data = null, bool $allowCustomClassName = true): SchemaInterface
    {
        if (!isset($this->schemas[$key])) {
            $className = $className && !$allowCustomClassName ? $className : $this->getSchemaClass($key, $className);

            $this->schemas[$key] = $this->getSchemaFactory()->createSchema($key, $className, $data ?? $this->getData($key), $this);
        }

        return $this->schemas[$key];
    }

    public function setSchemaFactory(SchemaFactory $factory): void
    {
        $this->schemaFactory = $factory;
    }

    public function setSchemaClass(string $key, string $className): void
    {
        $this->schemaClasses[$key] = $className;
    }

    public function setSchemaClasses(array $map): void
    {
        $this->schemaClasses = $map;
    }

    public function resetSchemas(): void
    {
        $this->schemas = [];
    }

    public function setSchema(string $key, SchemaInterface $schema): void
    {
        $this->schemas[$key] = $schema;
    }

    public function all(): array
    {
        return $this->data->all();
    }

    public function get(string $key)
    {
        return $this->data->get($key);
    }

    public function getData(string|null $key = null): Data
    {
        return null === $key ? $this->data : $this->data->getData($key, []);
    }

    public function is(string $key, bool $default = false): bool
    {
        return true === ($this->get($key) ?? $default);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParent(): SchemaInterface|null
    {
        return $this->parent;
    }

    public function setParent(SchemaInterface|null $schema): void
    {
        $this->parent = $schema;
    }

    public function getRoot(): SchemaInterface|null
    {
        $current = $this;

        while ($current instanceof ParentAwareSchemaInterface && $current->getParent()) {
            $current = $current->getParent();
        }

        return $current;
    }

    public function getPath(): Path
    {
        $path = [];
        $current = $this;

        while ($current instanceof ParentAwareSchemaInterface && $current->getParent()) {
            $path[] = $current->getName();
            $current = $current->getParent();
        }

        return new Path(array_reverse($path));
    }

    public function getDca(): Dca|null
    {
        $root = $this->getRoot();

        return $root instanceof Dca ? $root : null;
    }

    public function copyWith(Data $data): static
    {
        return $this->schemaFactory->createSchema(
            $this->getName(),
            static::class,
            $data,
            $this->parent,
            true,
        );
    }

    protected function getSchemaFactory(): SchemaFactory
    {
        return $this->schemaFactory;
    }

    /**
     * @param class-string|null $fallback
     *
     * @return class-string|null
     */
    protected function getSchemaClass(string $key, string|null $fallback = null): string|null
    {
        $className = $this->schemaClasses[$key] ?? null;

        if (!$className) {
            foreach (array_keys($this->schemaClasses) as $schemaKey) {
                if (!str_contains($schemaKey, '*')) {
                    continue;
                }

                if (preg_match('/'.str_replace('*', '.+', $schemaKey).'/i', $key)) {
                    $className = $this->schemaClasses[$schemaKey];
                }
            }
        }

        if (!$className && !$fallback) {
            throw new \LogicException(sprintf('No schema class defined for "%s" in %s', $key, static::class));
        }

        return $className ?? $fallback;
    }
}
