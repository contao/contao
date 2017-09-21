<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle;

/**
 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5; use the
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
