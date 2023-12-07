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

class ChildDataObserver implements DataObserverInterface
{
    public function __construct(private readonly Data $data)
    {
    }

    public function update(Data $subject): void
    {
        if (!$this->data->isRoot()) {
            $updated = $subject->get($this->data->getPath()) ?? [];

            if (!$this->data->isEqualTo($updated)) {
                $this->data->replace($updated);
            }
        }
    }
}
