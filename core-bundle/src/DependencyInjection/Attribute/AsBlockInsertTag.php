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
 * An attribute to register a block insert tag.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AsBlockInsertTag
{
    public function __construct(
        public string $name,
        public string $endTag,
        public int $priority = 0,
        public string|null $method = null,
        public bool|null $resolveNestedTags = null,
    ) {
    }
}
