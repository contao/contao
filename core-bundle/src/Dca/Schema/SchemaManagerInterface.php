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

interface SchemaManagerInterface
{
    /**
     * @template T of SchemaInterface
     *
     * @param class-string<T> $className
     *
     * @return T
     */
    public function getSchema(string $key, string $className, Data|null $data = null, bool $allowCustomClassName = true): SchemaInterface;

    public function setSchema(string $key, SchemaInterface $schema): void;

    public function setSchemaFactory(SchemaFactory $factory): void;

    /**
     * @param class-string $className
     */
    public function setSchemaClass(string $key, string $className): void;

    /**
     * @param array<string, class-string> $map
     */
    public function setSchemaClasses(array $map): void;

    public function resetSchemas(): void;
}
