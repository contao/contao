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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\DataContainerSubject;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class NewsArchiveAccessVoter extends Voter
{
    private const SUPPORTED_ATTRIBUTES = [
        ContaoCorePermissions::DC_ACTION_CREATE,
        ContaoCorePermissions::DC_ACTION_EDIT,
        ContaoCorePermissions::DC_ACTION_COPY,
        ContaoCorePermissions::DC_ACTION_DELETE,
        ContaoCorePermissions::DC_ACTION_VIEW,
    ];

    public function __construct(private ContaoFramework $contaoFramework, private Security $security)
    {
    }

    protected function supports(string $attribute, $subject)
    {
        return $subject instanceof DataContainerSubject &&
            'tl_news_archive' === $subject->table &&
            \in_array($attribute, self::SUPPORTED_ATTRIBUTES, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        return match ($attribute) {
            ContaoCorePermissions::DC_ACTION_CREATE,
            ContaoCorePermissions::DC_ACTION_COPY => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_CREATE_ARCHIVES),
            ContaoCorePermissions::DC_ACTION_EDIT,
            ContaoCorePermissions::DC_ACTION_VIEW => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, $subject->id),
            ContaoCorePermissions::DC_ACTION_DELETE => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_DELETE_ARCHIVES),
        };
    }
}
