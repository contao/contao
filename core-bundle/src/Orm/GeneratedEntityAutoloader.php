<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Orm;

class GeneratedEntityAutoloader
{
    public static function register($directory)
    {
        $namespace = 'Contao\CoreBundle\GeneratedEntity';

        $autoloader = function ($className) use ($directory, $namespace) {
            if (0 !== strpos($className, $namespace)) {
                return;
            }

            $name = substr($className, strlen($namespace));
            $name = str_replace('\\', '', $name);
            $file = sprintf('%s/%s.php', $directory, $name);

            require $file;
        };

        spl_autoload_register($autoloader);

        return $autoloader;
    }
}
