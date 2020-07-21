<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Exception;

use Contao\CoreBundle\Framework\Adapter;
use Contao\System;
use Webmozart\PathUtil\Path;

class LegacyRoutingException extends \LogicException
{
    /**
     * @param System&Adapter $systemAdapter
     */
    public static function getHooks(Adapter $systemAdapter, string $projectDir): array
    {
        $hooks = [];

        foreach (['getPageIdFromUrl', 'getRootPageFromUrl'] as $name) {
            if (empty($GLOBALS['TL_HOOKS'][$name]) || !\is_array($GLOBALS['TL_HOOKS'][$name])) {
                continue;
            }

            foreach ($GLOBALS['TL_HOOKS'][$name] as $callback) {
                $class = $systemAdapter->importStatic($callback[0]);
                $file = (new \ReflectionClass($class))->getFileName();
                $vendorDir = $projectDir.'/vendor/';
                $modulesDir = $projectDir.'/system/modules/';

                $hook = [
                    'name' => $name,
                    'class' => \get_class($class),
                    'method' => $callback[1],
                    'extension' => '',
                ];

                if (Path::isBasePath($vendorDir, $file)) {
                    [$vendor, $package] = explode('/', Path::makeRelative($file, $vendorDir), 3);
                    $hook['extension'] = $vendor.'/'.$package;
                } elseif (Path::isBasePath($modulesDir, $file)) {
                    [$module] = explode('/', Path::makeRelative($file, $modulesDir), 2);
                    $hook['extension'] = 'system/modules/'.$module;
                }

                $hooks[] = $hook;
            }
        }

        return $hooks;
    }
}
