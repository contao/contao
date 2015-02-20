<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\FaqBundle;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoBundle;

/**
 * Configures the Contao FAQ bundle.
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoFaqBundle extends ContaoBundle
{
    /**
     * {@inheritdoc}
     */
    public function getPublicFolders()
    {
        return [
            $this->getPath() . '/../contao/assets',
        ];
    }
}
