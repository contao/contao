<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\PageType\AliasRegexp;

use function preg_replace_callback;

class AliasRegexpCompiler
{
    public function compile(string $alias, array $requirements): string
    {
        return preg_replace_callback(
            '#\{(\w+)\}#',
            static function (array $matches) use ($requirements) {
                return $requirements[$matches[1]] ?: '[^/]+';
            },
            $alias
        );
    }
}
