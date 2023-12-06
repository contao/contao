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

class NewsletterChannelAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly Security $security)
    {
    }

    protected function getTable(): string
    {
        return 'tl_newsletter_channel';
    }

    protected function isGranted(UpdateAction|CreateAction|ReadAction|DeleteAction $action): bool
    {
        return match (true) {
            $action instanceof CreateAction => $this->security->isGranted(ContaoNewsletterPermissions::USER_CAN_CREATE_CHANNELS),
            $action instanceof ReadAction,
            $action instanceof UpdateAction => $this->security->isGranted(ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL, $action->getCurrentId()),
            $action instanceof DeleteAction => $this->security->isGranted(ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL, $action->getCurrentId())
                && $this->security->isGranted(ContaoNewsletterPermissions::USER_CAN_DELETE_CHANNELS),
            default => false,
        };
    }
}
