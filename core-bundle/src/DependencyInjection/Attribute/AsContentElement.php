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
 * Service tag to autoconfigure content elements.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class AsContentElement
{
    public array $attributes;

    /**
     * @param array{default?:array{allowedTypes?:list<string>}} $slots
     */
    public function __construct(string|null $type = null, string $category = 'miscellaneous', string|null $template = null, string|null $method = null, string|null $renderer = null, array $slots = [], mixed ...$attributes)
    {
        if ($slots && array_keys($slots) !== ['default']) {
            throw new \InvalidArgumentException(sprintf('Only the slot "default" is supported, got "%s".', implode('", "', array_keys($slots))));
        }

        $attributes['type'] = $type;
        $attributes['category'] = $category;
        $attributes['template'] = $template;
        $attributes['method'] = $method;
        $attributes['renderer'] = $renderer;
        $attributes['slots'] = $slots;

        $this->attributes = $attributes;
    }
}
