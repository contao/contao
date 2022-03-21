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

use Contao\DataContainer;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * By default, the Contao back end is fully accessible unless a developer wants to have specific
 * permissions. That's why this voter is implemented with a very low priority, so it allows everything
 * in the back end as the last voter in case no other voter decided to deny access before.
 */
class DefaultDcaVoter extends Voter
{
    protected function supports(string $attribute, $subject)
    {
        return str_starts_with($attribute, DataContainer::PERMISSION_PREFIX);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        return true;
    }
}
