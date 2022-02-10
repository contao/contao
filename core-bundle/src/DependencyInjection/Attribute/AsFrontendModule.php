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
 * Service tag to autoconfigure frontend module.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class AsFrontendModule
{
    public array $attributes;

    /**
     * @param mixed ...$attributes
     */
    public function __construct(string $type = null, string $category = 'miscellaneous', string $template = null, string $method = null, string $renderer = null, ...$attributes)
    {
        $attributes['type'] = $type;
        $attributes['category'] = $category;
        $attributes['template'] = $template;
        $attributes['method'] = $method;
        $attributes['renderer'] = $renderer;

        $this->attributes = $attributes;
    }
}
