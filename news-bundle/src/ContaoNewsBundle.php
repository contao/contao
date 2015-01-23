<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoBundle;

/**
 * Configures the Contao news bundle.
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoNewsBundle extends ContaoBundle
{
    /**
     * {@inheritdoc}
     */
    public function getPublicFolders()
    {
        return [
            $this->getPath() . '/../contao/assets'
        ];
    }
}
