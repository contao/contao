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

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class ContentCommentsVoter extends AbstractCommentsVoter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supportsSource(string $source): bool
    {
        return 'tl_content' === $source;
    }

    protected function hasAccess(TokenInterface $token, string $source, int $parent): bool
    {
        $page = $this->connection->fetchAssociative(
            'SELECT * FROM tl_page WHERE id=(SELECT pid FROM tl_article WHERE id=(SELECT pid FROM tl_content WHERE id=?))',
            [$parent],
        );

        // Do not check whether the page is mounted (see #5174)
        return false !== $page && $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_EDIT_ARTICLES], $page);
    }
}
