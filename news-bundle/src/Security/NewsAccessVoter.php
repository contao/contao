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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authorization\DcaSubject\ParentSubject;
use Contao\CoreBundle\Security\Authorization\DcaSubject\RecordSubject;
use Contao\CoreBundle\Security\Authorization\DcaSubject\RootSubject;
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

    /**
     * @var ContaoFramework
     */
    private $contaoFramework;

    public function __construct(AccessDecisionManagerInterface $accessDecisionManager, ContaoFramework $contaoFramework)
    {
        $this->accessDecisionManager = $accessDecisionManager;
        $this->contaoFramework = $contaoFramework;
    }

    protected function supportsTable(string $table): bool
    {
        return 'tl_news' === $table;
    }

    protected function voteOnAttribute(string $attribute, RootSubject $subject, BackendUser $user, TokenInterface $token): bool
    {
        // Listing of news is allowed if listing of news archives is allowed too
        if (AbstractDcaVoter::OPERATION_LIST === $attribute) {
            return $this->accessDecisionManager->decide(
                $token,
                [AbstractDcaVoter::OPERATION_LIST],
                new RootSubject('tl_news_archive')
            );
        }

        if ($subject instanceof ParentSubject) {
            $newsArchiveId = (int) $subject->getParentId();
        } elseif ($subject instanceof RecordSubject) {
            $news = $this->contaoFramework->getAdapter(NewsModel::class)->findById((int) $subject->getId());

            if (null !== $news) {
                $newsArchiveId = $news->pid;
            }
        } else {
            $newsArchiveId = 0;
        }

        // All operations are allowed if the user is allowed to see (aka "work with") the news archive
        return $this->accessDecisionManager->decide(
            $token,
            [AbstractDcaVoter::OPERATION_SHOW],
            new RecordSubject('tl_news_archive', (string) $newsArchiveId)
        );
    }

    protected function getSubjectByAttributes(): array
    {
        return [
            AbstractDcaVoter::OPERATION_LIST => RootSubject::class,
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
