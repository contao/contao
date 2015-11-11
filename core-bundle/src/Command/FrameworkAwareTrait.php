<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;

/**
 * Provides methods to inject the framework service.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
trait FrameworkAwareTrait
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Returns the framework service.
     *
     * @return ContaoFrameworkInterface The framework service
     *
     * @throws \LogicException If the framework service is not set
     */
    public function getFramework()
    {
        if (null === $this->framework) {
            throw new \LogicException('The framework service has not been set.');
        }

        return $this->framework;
    }

    /**
     * Sets the framework service.
     *
     * @param ContaoFrameworkInterface $framework The framework service
     */
    public function setFramework(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }
}
