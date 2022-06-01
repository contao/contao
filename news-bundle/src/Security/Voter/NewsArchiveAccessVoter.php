<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Security\Voter;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class NewsArchiveAccessVoter extends Voter
{
    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, $subject)
    {
        return str_starts_with($attribute, ContaoCorePermissions::DC_PREFIX)
            && ($subject instanceof CreateAction || $subject instanceof ReadAction || $subject instanceof UpdateAction || $subject instanceof DeleteAction)
            && 'tl_news_archive' === $subject->getDataSource();
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return match (true) {
            $subject instanceof CreateAction => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_CREATE_ARCHIVES),
            $subject instanceof ReadAction => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, $subject->getCurrentId()),
            $subject instanceof UpdateAction => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, $subject->getCurrentId()),
            $subject instanceof DeleteAction => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, $subject->getCurrentId()) && $this->security->isGranted(ContaoNewsPermissions::USER_CAN_DELETE_ARCHIVES),
            default => false,
        };
    }
}
