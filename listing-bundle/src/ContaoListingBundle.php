<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ListingBundle;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoBundle;

/**
 * Configures the Contao listing bundle.
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoListingBundle extends ContaoBundle
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
