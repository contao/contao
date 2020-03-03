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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authorization\DcaPermission;
use Contao\CoreBundle\Security\Authorization\DcaSubject\ParentSubject;
use Contao\CoreBundle\Security\Authorization\DcaSubject\RecordSubject;
use Contao\CoreBundle\Security\Voter\AbstractDcaVoter;
use Contao\NewsModel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class NewsAccessVoter extends AbstractDcaVoter
{
    /**
     * @var AccessDecisionManagerInterface
     */
    private $accessDecisionManager;

    public function __construct(AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->accessDecisionManager = $accessDecisionManager;
    }

    protected function getTable(): string
    {
        return 'tl_news';
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->getBackendUser($token);

        if (null === $user) {
            return false;
        }


        switch ($attribute) {
            case AbstractDcaVoter::OPERATION_CREATE:
            case AbstractDcaVoter::OPERATION_PASTE:
                // Creating and pasting is based on whether we are allowed
                /** @var ParentSubject $subject */
                return $this->accessDecisionManager->decide($token, [AbstractDcaVoter::OPERATION_SHOW],);

            case AbstractDcaVoter::OPERATION_EDIT:
            case AbstractDcaVoter::OPERATION_DELETE:
            case AbstractDcaVoter::OPERATION_COPY:
            case AbstractDcaVoter::OPERATION_CUT:
            case AbstractDcaVoter::OPERATION_SHOW:
                /** @var RecordSubject $subject */
                $subject = 'foo';
        }

        // News 

        return false;
    }

    protected function getSubjectByAttributes(): array
    {
        return [
            AbstractDcaVoter::OPERATION_CREATE => ParentSubject::class,
            AbstractDcaVoter::OPERATION_EDIT => RecordSubject::class,
            AbstractDcaVoter::OPERATION_DELETE => RecordSubject::class,
            AbstractDcaVoter::OPERATION_COPY => RecordSubject::class,
            AbstractDcaVoter::OPERATION_CUT => RecordSubject::class,
            AbstractDcaVoter::OPERATION_SHOW => RecordSubject::class,
            AbstractDcaVoter::OPERATION_PASTE => ParentSubject::class,
        ];
    }
}
