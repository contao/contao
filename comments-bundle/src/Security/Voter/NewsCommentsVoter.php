<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CommentsBundle\Security\Voter;

use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class NewsCommentsVoter extends AbstractCommentsVoter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
        parent::__construct($this->accessDecisionManager);
    }

    protected function supportsSource(string $source): bool
    {
        return 'tl_news' === $source;
    }

    protected function hasAccess(TokenInterface $token, string $source, int $parent): bool
    {
        $archiveId = $this->connection->fetchOne(
            'SELECT pid FROM tl_news WHERE id=?',
            [$parent],
        );

        return false !== $archiveId && $this->accessDecisionManager->decide($token, [ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE], $archiveId);
    }
}
