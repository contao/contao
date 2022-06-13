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
use Contao\CoreBundle\Security\DataContainer\DataContainerSubject;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class NewsArchiveAccessVoter extends Voter
{
    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof DataContainerSubject
            && 'tl_news_archive' === $subject->table
            && str_starts_with($attribute, ContaoCorePermissions::DC_ACTION_PREFIX);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return match ($attribute) {
            ContaoCorePermissions::DC_ACTION_CREATE => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_CREATE_ARCHIVES),
            ContaoCorePermissions::DC_ACTION_COPY => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_CREATE_ARCHIVES)
                && $this->security->isGranted(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, $subject->id),
            ContaoCorePermissions::DC_ACTION_EDIT,
            ContaoCorePermissions::DC_ACTION_VIEW => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, $subject->id),
            ContaoCorePermissions::DC_ACTION_DELETE => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, $subject->id)
                && $this->security->isGranted(ContaoNewsPermissions::USER_CAN_DELETE_ARCHIVES),
            default => false,
        };
    }
}
