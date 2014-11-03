<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoBundle;

/**
 * Configures the Contao calendar bundle.
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoCalendarBundle extends ContaoBundle
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
