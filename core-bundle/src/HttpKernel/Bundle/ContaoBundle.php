<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Configures a Contao bundle.
 *
 * @author Leo Feyer <https://contao.org>
 */
abstract class ContaoBundle extends Bundle implements ContaoBundleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPublicFolders()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getContaoResourcesPath()
    {
        return $this->getPath() . '/../contao';
    }
}
