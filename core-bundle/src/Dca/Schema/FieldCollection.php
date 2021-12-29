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
 * @extends SchemaCollection<Field>
 */
class FieldCollection extends SchemaCollection
{
    public function field(string $key): Field
    {
        return $this->getSchema($key, Field::class);
    }

    protected function getChildSchema(): string
    {
        return Field::class;
    }
}
