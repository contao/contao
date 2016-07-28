<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework;

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
     * @return ContaoFrameworkInterface
     *
     * @throws \LogicException
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
     * @param ContaoFrameworkInterface|null $framework
     */
    public function setFramework(ContaoFrameworkInterface $framework = null)
    {
        $this->framework = $framework;
    }
}
