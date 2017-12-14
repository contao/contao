<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework;

/**
 * Interface for framework aware services.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
interface FrameworkAwareInterface
{
    /**
     * Sets the framework service.
     *
     * @param ContaoFrameworkInterface|null $framework
     */
    public function setFramework(ContaoFrameworkInterface $framework = null);
}
