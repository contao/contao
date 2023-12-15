<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event\Dca;

use Contao\CoreBundle\Dca\Data;
use Contao\CoreBundle\Dca\Schema\SchemaInterface;
use Symfony\Contracts\EventDispatcher\Event;

class SchemaCreatingEvent extends Event
{
    public function __construct(
        private readonly Data $data,
        /**
         * @var class-string<SchemaInterface>
         */
        private readonly string $schema,
        private readonly string $name,
        private readonly SchemaInterface|null $parent,
    ) {
    }

    public function getData(): Data
    {
        return $this->data;
    }

    /**
     * @return class-string<SchemaInterface>
     */
    public function getSchema(): string
    {
        return $this->schema;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParent(): SchemaInterface|null
    {
        return $this->parent;
    }
}
