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

use Contao\CoreBundle\Dca\Schema\SchemaInterface;
use Symfony\Contracts\EventDispatcher\Event;

class SchemaCreatedEvent extends Event
{
    public function __construct(
        private SchemaInterface $schema,
        private readonly bool $triggerValidation = false,
    ) {
    }

    public function getSchema(): SchemaInterface
    {
        return $this->schema;
    }

    public function setSchema(SchemaInterface $schema): void
    {
        $this->schema = $schema;
    }

    public function triggerValidation(): bool
    {
        return $this->triggerValidation;
    }
}
