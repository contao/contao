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

interface DataCallbackObserverInterface extends DataObserverInterface
{
    public function setCallback(callable|null $callback): self;

    public function runCallback(Data $subject): void;
}
