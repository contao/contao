<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Content;

use Symfony\Component\DependencyInjection\Container;

abstract class AbstractPageProvider implements PageProviderInterface
{
    public static function getPageType(): string
    {
        $className = static::class;
        $className = ltrim(strrchr($className, '\\'), '\\');

        if ('PageProvider' === substr($className, -12)) {
            $className = substr($className, 0, -12);
        } elseif ('Provider' === substr($className, -8)) {
            $className = substr($className, 0, -8);
        }

        return Container::underscore($className);
    }

    public function getUrlSuffixes(): array
    {
        return [];
    }
}
