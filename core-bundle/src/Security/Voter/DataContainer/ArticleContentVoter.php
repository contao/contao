<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter\DataContainer;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @internal
 */
class ArticleContentVoter extends AbstractDynamicPtableVoter
{
    private array $pageIds = [];

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Connection $connection,
    ) {
        parent::__construct($connection);
    }

    public function reset(): void
    {
        parent::reset();

        $this->pageIds = [];
    }

    protected function getTable(): string
    {
        return 'tl_content';
    }

    protected function hasAccessToRecord(TokenInterface $token, string $table, int $id): bool
    {
        if ('tl_article' !== $table) {
            return true;
        }

        if (!$this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'article')) {
            return false;
        }

        $pageId = $this->getPageId($id);

        return $pageId
            && $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_PAGE], $pageId)
            && $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_EDIT_ARTICLES], $pageId);
    }

    private function getPageId(int $articleId): int|null
    {
        if (!\array_key_exists($articleId, $this->pageIds)) {
            $pid = $this->connection->fetchOne('SELECT pid FROM tl_article WHERE id=?', [$articleId]);
            $this->pageIds[$articleId] = false !== $pid ? (int) $pid : null;
        }

        return $this->pageIds[$articleId];
    }
}
