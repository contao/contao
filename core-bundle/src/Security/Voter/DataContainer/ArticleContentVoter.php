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
    /**
     * @var array<int, array{id: int, type: string}>
     */
    private array $pageMap = [];

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Connection $connection,
    ) {
        parent::__construct($connection);
    }

    public function reset(): void
    {
        parent::reset();

        $this->pageMap = [];
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

        $page = $this->getPage($id);

        return $page
            && $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_PAGE], $page['id'])
            && $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_EDIT_ARTICLES], $page['id'])
            && $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE], $page['type']);
    }

    /**
     * @return array{id: int, type: string}|null
     */
    private function getPage(int $articleId): array|null
    {
        if (!\array_key_exists($articleId, $this->pageMap)) {
            $this->pageMap[$articleId] = null;

            $record = $this->connection->fetchAssociative('SELECT id, type FROM tl_page WHERE id=(SELECT pid FROM tl_article WHERE id=?)', [$articleId]);

            if (false !== $record) {
                $this->pageMap[$articleId] = $record;
            }
        }

        return $this->pageMap[$articleId];
    }
}
