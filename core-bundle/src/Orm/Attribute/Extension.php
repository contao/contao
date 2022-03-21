<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Orm\Attribute;

#[\Attribute]
final class Extension
{
    public function __construct(public readonly string $entity, public readonly array $indexes = [],)
    {
    }
}
