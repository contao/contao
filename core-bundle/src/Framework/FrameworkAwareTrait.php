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
    protected ContaoFramework|null $framework = null;

    public function setFramework(ContaoFramework|null $framework = null): void
    {
        $this->framework = $framework;
    }
}
