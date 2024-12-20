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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @internal
 */
class NewsletterChannelAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    protected function getTable(): string
    {
        return 'tl_newsletter_channel';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if (!$this->accessDecisionManager->decide($token, [ContaoNewsletterPermissions::USER_CAN_ACCESS_MODULE])) {
            return false;
        }

        return match (true) {
            $action instanceof CreateAction => $this->accessDecisionManager->decide($token, [ContaoNewsletterPermissions::USER_CAN_CREATE_CHANNELS]),
            $action instanceof ReadAction,
            $action instanceof UpdateAction => $this->accessDecisionManager->decide($token, [ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL], $action->getCurrentId()),
            $action instanceof DeleteAction => $this->accessDecisionManager->decide($token, [ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL], $action->getCurrentId())
                && $this->accessDecisionManager->decide($token, [ContaoNewsletterPermissions::USER_CAN_DELETE_CHANNELS]),
        };
    }
}
