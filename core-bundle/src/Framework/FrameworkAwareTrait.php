<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework;

/**
 * Provides methods to inject the framework service.
 */
trait FrameworkAwareTrait
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Sets the framework service.
     *
     * @param ContaoFrameworkInterface|null $framework
     */
    public function setFramework(ContaoFrameworkInterface $framework = null): void
    {
        $this->framework = $framework;
    }

    /**
     * Returns the framework service.
     *
     * @throws \LogicException
     *
     * @return ContaoFrameworkInterface
     *
     * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0
     */
    public function getFramework(): ContaoFrameworkInterface
    {
        @trigger_error(
            'Using FrameworkAwareTrait::getFramework() has been deprecated and will no longer work in Contao 5.0.',
            E_USER_DEPRECATED
        );

        if (null === $this->framework) {
            throw new \LogicException('The framework service has not been set.');
        }

        return $this->framework;
    }
}
