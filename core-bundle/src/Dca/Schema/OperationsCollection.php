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

/**
 * @extends SchemaCollection<Operation>
 */
class OperationsCollection extends SchemaCollection
{
    public function operation(string $name): Operation
    {
        return $this->getSchema($name, $this->getChildSchema());
    }

    protected function getChildSchema(): string
    {
        return Operation::class;
    }
}
