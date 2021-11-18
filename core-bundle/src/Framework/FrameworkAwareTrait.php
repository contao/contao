<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Framework;

trait FrameworkAwareTrait
{
    protected ?ContaoFramework $framework = null;

    public function setFramework(ContaoFramework $framework = null): void
    {
        $this->framework = $framework;
    }

    /**
     * @throws \LogicException
     *
     * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0
     */
    public function getFramework(): ContaoFramework
    {
        trigger_deprecation('contao/core-bundle', '4.3', 'Using "Contao\CoreBundle\Framework\FrameworkAwareTrait::getFramework()" has been deprecated and will no longer work in Contao 5.0.');

        if (null === $this->framework) {
            throw new \LogicException('The framework service has not been set.');
        }

        return $this->framework;
    }
}
