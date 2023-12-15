<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Security\Voter;

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Contao\NewsletterBundle\Security\ContaoNewsletterPermissions;
use Symfony\Bundle\SecurityBundle\Security;

class NewsletterAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly Security $security)
    {
    }

    protected function getTable(): string
    {
        return 'tl_newsletter';
    }

    protected function isGranted(CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        $pid = match (true) {
            $action instanceof CreateAction => $action->getNewPid(),
            $action instanceof ReadAction,
            $action instanceof UpdateAction,
            $action instanceof DeleteAction => $action->getCurrentPid(),
        };

        return $this->security->isGranted(ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL, $pid);
    }
}
