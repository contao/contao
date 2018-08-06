<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment;

use Contao\CoreBundle\Fragment\Reference\FragmentReference;

interface FragmentPreHandlerInterface
{
    /**
     * Allows to modify the fragment reference and configuration.
     */
    public function preHandleFragment(FragmentReference $uri, FragmentConfig $config): void;
}
