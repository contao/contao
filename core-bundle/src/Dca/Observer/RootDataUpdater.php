<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Observer;

use Contao\CoreBundle\Dca\Data;

class RootDataUpdater implements DataObserverInterface
{
    public function update(Data $subject): void
    {
        $subject->getRoot()->set($subject->getPath(), $subject->all());
    }
}
