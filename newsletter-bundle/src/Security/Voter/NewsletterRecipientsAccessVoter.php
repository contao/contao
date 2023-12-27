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
use Contao\CoreBundle\Security\Voter\DataContainer\ParentAccessTrait;
use Contao\NewsletterBundle\Security\ContaoNewsletterPermissions;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @internal
 */
class NewsletterRecipientsAccessVoter extends AbstractDataContainerVoter
{
    use ParentAccessTrait;

    protected function getTable(): string
    {
        return 'tl_newsletter_recipients';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        return $this->accessDecisionManager->decide($token, [ContaoNewsletterPermissions::USER_CAN_ACCESS_MODULE])
            && $this->hasAccessToParent($token, ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL, $action);
    }
}
