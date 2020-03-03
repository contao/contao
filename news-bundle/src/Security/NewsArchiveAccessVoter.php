<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Security;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Authorization\DcaSubject\RecordSubject;
use Contao\CoreBundle\Security\Authorization\DcaSubject\RootSubject;
use Contao\CoreBundle\Security\Voter\AbstractDcaVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class NewsArchiveAccessVoter extends AbstractDcaVoter
{
    protected function supportsTable(string $table): string
    {
        return 'tl_news_archive' === $table;
    }

    protected function voteOnAttribute(string $attribute, RootSubject $subject, BackendUser $user, TokenInterface $token): bool
    {
        $allowedNewsArchives = array_map('intval', (array) $user->news);

        switch ($attribute) {
            case AbstractDcaVoter::OPERATION_LIST:
                // TODO: check back end module access
                return true;

            case AbstractDcaVoter::OPERATION_CREATE:
            case AbstractDcaVoter::OPERATION_PASTE:

                return $user->hasAccess('create', 'newp');

            case AbstractDcaVoter::OPERATION_DELETE:
                if (!$user->hasAccess('delete', 'newp')) {
                    return false;
                }

                /** @var RecordSubject $subject */
                return \in_array((int) $subject->getId(), $allowedNewsArchives, true);

            case AbstractDcaVoter::OPERATION_EDIT:
            case AbstractDcaVoter::OPERATION_COPY:
            case AbstractDcaVoter::OPERATION_CUT:
            case AbstractDcaVoter::OPERATION_SHOW:

                /** @var RecordSubject $subject */
                return \in_array((int) $subject->getId(), $allowedNewsArchives, true);
        }
    }

    protected function getSubjectByAttributes(): array
    {
        return [
            AbstractDcaVoter::OPERATION_LIST => RootSubject::class,
            AbstractDcaVoter::OPERATION_CREATE => RootSubject::class,
            AbstractDcaVoter::OPERATION_EDIT => RecordSubject::class,
            AbstractDcaVoter::OPERATION_DELETE => RecordSubject::class,
            AbstractDcaVoter::OPERATION_COPY => RecordSubject::class,
            AbstractDcaVoter::OPERATION_CUT => RecordSubject::class,
            AbstractDcaVoter::OPERATION_SHOW => RecordSubject::class,
            AbstractDcaVoter::OPERATION_PASTE => RootSubject::class,
        ];
    }
}
