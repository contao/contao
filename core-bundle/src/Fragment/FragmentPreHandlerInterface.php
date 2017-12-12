<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Fragment;

use Contao\CoreBundle\Fragment\Reference\FragmentReference;

interface FragmentPreHandlerInterface
{
    /**
     * Allows to modify the fragment reference and configuration.
     *
     * @param FragmentReference $uri
     * @param FragmentConfig    $config
     */
    public function preHandleFragment(FragmentReference $uri, FragmentConfig $config): void;
}
