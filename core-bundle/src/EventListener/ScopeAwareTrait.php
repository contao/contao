<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

@trigger_error(
    'Using ' . __NAMESPACE__ . '\ScopeAwareTrait has been deprecated and will no longer work in Contao 5.0. '
        . 'Use Contao\CoreBundle\Framework\ScopeAwareTrait instead.',
    E_USER_DEPRECATED
);

/**
 * Provides methods to test the request scope.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.
 *             Use Contao\CoreBundle\Framework\ScopeAwareTrait instead.
 */
trait ScopeAwareTrait
{
    use \Contao\CoreBundle\Framework\ScopeAwareTrait;
}
