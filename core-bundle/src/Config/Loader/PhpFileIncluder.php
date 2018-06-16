<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Config\Loader;

use Symfony\Component\Config\Loader\Loader;

/**
 * Includes the given php file, e.g. for old php/GLOBALS based translations
 *
 * @author Mike vom Scheidt <https://github.com/mvscheidt>
 */
class PhpFileIncluder extends Loader
{
    /**
     * Includes a php file
     *
     * @param string      $file
     * @param string|null $type
     *
     * @return void
     */
    public function load($file, $type = null)
    {
        $this->includePhp($file);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'php' === pathinfo((string) $resource, PATHINFO_EXTENSION);
    }

    /**
     * includes the given file
     *
     * @param $file
     */
    private function includePhp($file)
    {
        include $file;
    }
}
