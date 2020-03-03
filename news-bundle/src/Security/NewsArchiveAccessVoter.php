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

use Contao\CoreBundle\Security\Authorization\DcaSubject\RecordSubject;
use Contao\CoreBundle\Security\Authorization\DcaSubject\RootSubject;
use Contao\CoreBundle\Security\Voter\AbstractDcaVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class NewsArchiveAccessVoter extends AbstractDcaVoter
{
    protected function getTable(): string
    {
        return 'tl_news_archive';
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->getBackendUser($token);

        if (null === $user) {
            return false;
        }

        $allowedNewsArchives = array_map('intval', (array) $user->news);

        switch ($attribute) {
            case AbstractDcaVoter::OPERATION_CREATE:
            case AbstractDcaVoter::OPERATION_PASTE:

                return $user->hasAccess('create', 'newp');

            case AbstractDcaVoter::OPERATION_DELETE:
                if (!$user->hasAccess('delete', 'newp')) {
                    return false;
                }

                return \in_array($newsArchiveId, $allowedNewsArchives, true);

            case AbstractDcaVoter::OPERATION_EDIT:
            case AbstractDcaVoter::OPERATION_COPY:
            case AbstractDcaVoter::OPERATION_CUT:
            case AbstractDcaVoter::OPERATION_SHOW:
                return \in_array($newsArchiveId, $allowedNewsArchives, true);
        }
    }

    protected function getSubjectByAttributes(): array
    {
        return [
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
