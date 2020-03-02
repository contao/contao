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
    private $framework;

    /**
     * NewsAccessVoter constructor.
     */
    public function __construct(AccessDecisionManagerInterface $accessDecisionManager, ContaoFramework $framework)
    {
        $this->accessDecisionManager = $accessDecisionManager;
        $this->framework = $framework;
    }

    protected function getTable(): string
    {
        return 'tl_news';
    }

    /**
     * @param DcaPermission $subject
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->getBackendUser($token);

        if (null === $user) {
            return false;
        }

        $allowedNewsArchives = array_map('intval', (array) $user->news);

        if (0 === \count($allowedNewsArchives)) {
            return true;
        }

        $newsId = (int) $subject->getId();

        if (0 === $newsId) {
            return true;
        }

        if ($this->isCollectionOperation($attribute)) {
            return $this->accessDecisionManager->decide($token, [$attribute], new DcaPermission('tl_news_archive', $subject->getId()));
        }

        $news = $this->framework->getAdapter(NewsModel::class)->findById($newsId);

        if (null === $news) {
            return false;
        }

        return $this->accessDecisionManager->decide($token, [$attribute], new DcaPermission('tl_news_archive', $news->pid));
    }

    protected function getItemOperations(): array
    {
        return array_merge(parent::getItemOperations(), ['toggle', 'feature']);
    }
}
