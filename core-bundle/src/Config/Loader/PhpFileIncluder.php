<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Config\Loader;

use Symfony\Component\Config\Loader\Loader;

class PhpFileIncluder extends Loader
{
    /**
     * {@inheritdoc}
     */
    public function load($file, $type = null): void
    {
        include $file;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null): bool
    {
        return 'php' === pathinfo((string) $resource, PATHINFO_EXTENSION);
    }
}
