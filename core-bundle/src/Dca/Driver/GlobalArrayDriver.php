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

use Contao\Controller;
use Contao\CoreBundle\Dca\Observer\DataCallbackObserverInterface;
use Contao\CoreBundle\Dca\Observer\DriverDataChangeDataCallbackObserver;
use Contao\CoreBundle\Framework\ContaoFramework;

/**
 * Special array driver that reads data from $GLOBALS['TL_DCA'].
 */
class GlobalArrayDriver extends ArrayDriver implements MutableDataDriverInterface
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function getMutableDataObserver(string $resource): DataCallbackObserverInterface
    {
        return new DriverDataChangeDataCallbackObserver($resource, $this);
    }

    public function handles(string $resource): bool
    {
        $this->framework->getAdapter(Controller::class)->loadDataContainer($resource);

        return \is_array($GLOBALS['TL_DCA'][$resource] ?? null);
    }

    protected function getData(string $name): array
    {
        $this->framework->getAdapter(Controller::class)->loadDataContainer($name);

        return $GLOBALS['TL_DCA'][$name] ?? [];
    }
}
