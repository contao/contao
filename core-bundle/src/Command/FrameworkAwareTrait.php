<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

@trigger_error(
    'Using ' . __NAMESPACE__ . '\FrameworkAwareTrait has been deprecated and will no longer work in Contao 5.0. '
        . 'Use Contao\CoreBundle\Framework\FrameworkAwareTrait instead.',
    E_USER_DEPRECATED
);

/**
 * Provides methods to inject the framework service.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5.
 *             Use Contao\CoreBundle\Framework\FrameworkAwareTrait instead.
 */
trait FrameworkAwareTrait
{
    use \Contao\CoreBundle\Framework\FrameworkAwareTrait;
}
