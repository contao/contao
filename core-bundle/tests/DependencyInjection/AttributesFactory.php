<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection;

use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;

/**
 * We're using a class that is excluded from autoloading for these constructs,
 * because they would trigger a syntax error for PHP<8.0.
 */
class AttributesFactory
{
    public static function getAsContentElement(): AsContentElement
    {
        return new AsContentElement(
            type: 'content_element/text',
            template: 'a_template',
            method: 'aMethod',
            renderer: 'inline',
            foo: 'bar',
            baz: 42,
        );
    }

    public static function getAsFrontendModule(): AsFrontendModule
    {
        return new AsFrontendModule(
            type: 'frontend_module/navigation',
            template: 'a_template',
            method: 'aMethod',
            renderer: 'inline',
            foo: 'bar',
            baz: 42,
        );
    }
}
