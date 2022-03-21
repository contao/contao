<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Orm;

class GeneratedEntityAutoloader
{
    public static function register(string $directory): callable
    {
        $namespace = 'Contao\CoreBundle\GeneratedEntity';

        $autoloader = function (string $className) use ($directory, $namespace): void {
            if (!str_starts_with($className, $namespace)) {
                return;
            }

            $name = substr($className, \strlen($namespace));
            $name = str_replace('\\', '', $name);
            $file = sprintf('%s/%s.php', $directory, $name);

            require $file;
        };

        spl_autoload_register($autoloader);

        return $autoloader;
    }
}
