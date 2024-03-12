<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter\DataContainer;

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;

trait ActionPropertyTrait
{
    private function getProperty(string $property, CreateAction|DeleteAction|ReadAction|UpdateAction $action): array
    {
        $values = [];

        if (!$action instanceof CreateAction && isset($action->getCurrent()[$property])) {
            $values[] = $action->getCurrent()[$property];
        }

        if (!$action instanceof DeleteAction && !$action instanceof ReadAction && isset($action->getNew()[$property])) {
            $values[] = $action->getNew()[$property];
        }

        return array_unique($values);
    }
}
