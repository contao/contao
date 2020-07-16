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

use Contao\BackendUser;
use Contao\PageModel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BackendAccessVoter extends Voter
{
    private const PAGE_PERMISSIONS = [
        'can_edit_page' => BackendUser::CAN_EDIT_PAGE,
        'can_edit_page_hierarchy' => BackendUser::CAN_EDIT_PAGE_HIERARCHY,
        'can_delete_page' => BackendUser::CAN_DELETE_PAGE,
        'can_edit_articles' => BackendUser::CAN_EDIT_ARTICLES,
        'can_edit_article_hierarchy' => BackendUser::CAN_EDIT_ARTICLE_HIERARCHY,
        'can_delete_articles' => BackendUser::CAN_DELETE_ARTICLES,
    ];

    protected function supports($attribute, $subject): bool
    {
        return \is_string($attribute) && 0 === strncmp($attribute, 'contao_user.', 12);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return false;
        }

        $permission = explode('.', $attribute, 3);

        if ('contao_user' !== $permission[0] || !isset($permission[1])) {
            return false;
        }

        $field = $permission[1];

        if (!$subject && isset($permission[2])) {
            $subject = $permission[2];
        }

        if ('can_edit_fields' === $field) {
            return $this->canEditFieldsOf($subject, $user);
        }

        if (isset(self::PAGE_PERMISSIONS[$field])) {
            return $this->isAllowed($subject, self::PAGE_PERMISSIONS[$field], $user);
        }

        return $this->hasAccess($subject, $field, $user);
    }

    /**
     * Checks the user permissions against a field in tl_user(_group).
     */
    private function hasAccess($subject, string $field, BackendUser $user): bool
    {
        if (!is_scalar($subject) && !\is_array($subject)) {
            return false;
        }

        return $user->hasAccess($subject, $field);
    }

    /**
     * Checks if the user has access to a given page (tl_page.includeChmod et al.).
     */
    private function isAllowed($subject, int $flag, BackendUser $user): bool
    {
        if ($subject instanceof PageModel) {
            $subject->loadDetails();
            $subject = $subject->row();
        }

        if (!\is_array($subject)) {
            return false;
        }

        return $user->isAllowed($flag, $subject);
    }

    /**
     * Checks if the user has access to any field of a table (against tl_user(_group).alexf).
     */
    private function canEditFieldsOf($subject, BackendUser $user): bool
    {
        if (!\is_string($subject)) {
            return false;
        }

        return $user->canEditFieldsOf($subject);
    }
}
