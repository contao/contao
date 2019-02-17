<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle;

/**
 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5.0; use the
 *             Contao\CoreBundle\Framework\ContaoFrameworkInterface interface instead
 */
interface ContaoFrameworkInterface
{
    /**
     * Checks if the framework has been initialized.
     *
     * @return bool
     */
    public function isInitialized();

    /**
     * Initializes the framework.
     */
    public function initialize();
}
