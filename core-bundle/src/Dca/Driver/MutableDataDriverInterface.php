<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Driver;

use Contao\CoreBundle\Dca\Observer\DataCallbackObserverInterface;

interface MutableDataDriverInterface
{
    public function getMutableDataObserver(string $resource): DataCallbackObserverInterface;

    /**
     * Check if the source data for a given resource has changed.
     */
    public function hasChanged(string $resource): bool;
}
