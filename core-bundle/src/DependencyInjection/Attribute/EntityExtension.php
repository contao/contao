<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Attribute;

#[\Attribute(flags: \Attribute::TARGET_CLASS)]
final class EntityExtension
{
    public function __construct(public readonly string $entity)
    {
    }
}
