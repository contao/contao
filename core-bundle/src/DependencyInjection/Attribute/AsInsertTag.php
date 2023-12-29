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

/**
 * An attribute to register an insert tag.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AsInsertTag
{
    public function __construct(
        public string $name,
        public bool $asFragment = false,
        public int $priority = 0,
        public string|null $method = null,
        public bool|null $resolveNestedTags = null,
    ) {
    }
}
