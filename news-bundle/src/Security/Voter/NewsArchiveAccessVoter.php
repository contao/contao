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

use Contao\BackendUser;
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
        // Action global
        ContaoCorePermissions::DC_ACTION_CREATE,

        // Action on item
        ContaoCorePermissions::DC_ACTION_EDIT,
        ContaoCorePermissions::DC_ACTION_COPY,
        ContaoCorePermissions::DC_ACTION_DELETE,
        ContaoCorePermissions::DC_ACTION_VIEW,

        // View global
        ContaoCorePermissions::DC_VIEW_CREATE,
        ContaoCorePermissions::DC_GLOBAL_OPERATION_PREFIX.'feeds',

        // View on item
        ContaoCorePermissions::DC_OPERATION_PREFIX.'edit',
        ContaoCorePermissions::DC_OPERATION_PREFIX.'editheader',
        ContaoCorePermissions::DC_OPERATION_PREFIX.'copy',
        ContaoCorePermissions::DC_OPERATION_PREFIX.'delete',
        ContaoCorePermissions::DC_OPERATION_PREFIX.'show',
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
            // Actions
            ContaoCorePermissions::DC_ACTION_CREATE => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_CREATE_ARCHIVES),
            ContaoCorePermissions::DC_ACTION_EDIT,
            ContaoCorePermissions::DC_ACTION_COPY,
            ContaoCorePermissions::DC_ACTION_VIEW, => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, $subject->id),
            ContaoCorePermissions::DC_ACTION_DELETE => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_DELETE_ARCHIVES),

            // View global
            ContaoCorePermissions::DC_VIEW_CREATE => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_CREATE_ARCHIVES),
            ContaoCorePermissions::DC_GLOBAL_OPERATION_PREFIX.'feeds' => $this->canDisplayFeedOperation($token),

            // View item
            ContaoCorePermissions::DC_OPERATION_PREFIX.'edit',
            ContaoCorePermissions::DC_OPERATION_PREFIX.'show' => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, $subject->id),
            ContaoCorePermissions::DC_OPERATION_PREFIX.'editheader' => $this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_news_archive'),
            ContaoCorePermissions::DC_OPERATION_PREFIX.'copy' => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_CREATE_ARCHIVES),
            ContaoCorePermissions::DC_OPERATION_PREFIX.'delete' => $this->security->isGranted(ContaoNewsPermissions::USER_CAN_DELETE_ARCHIVES),
        };
    }

    private function canDisplayFeedOperation(TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return false;
        }

        return $user->isAdmin || !empty($user->newsfeeds) || !empty($user->newsfeedp);
    }
}
