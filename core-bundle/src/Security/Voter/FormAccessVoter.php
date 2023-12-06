<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Symfony\Bundle\SecurityBundle\Security;

class FormAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly Security $security)
    {
    }

    protected function getTable(): string
    {
        return 'tl_form';
    }

    protected function isGranted(UpdateAction|CreateAction|ReadAction|DeleteAction $action): bool
    {
        return match (true) {
            $action instanceof CreateAction => $this->security->isGranted(ContaoCorePermissions::USER_CAN_CREATE_FORMS),
            $action instanceof ReadAction,
            $action instanceof UpdateAction => $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_FORM, $action->getCurrentId()),
            $action instanceof DeleteAction => $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_FORM, $action->getCurrentId())
                && $this->security->isGranted(ContaoCorePermissions::USER_CAN_DELETE_FORMS),
        };
    }
}
