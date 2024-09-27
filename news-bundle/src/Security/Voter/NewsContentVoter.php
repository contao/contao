<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Security\Voter;

use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDynamicPtableVoter;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @internal
 */
class NewsContentVoter extends AbstractDynamicPtableVoter
{
    private array $archives = [];

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Connection $connection,
    ) {
        parent::__construct($connection);
    }

    public function reset(): void
    {
        parent::reset();

        $this->archives = [];
    }

    protected function getTable(): string
    {
        return 'tl_content';
    }

    protected function hasAccessToRecord(TokenInterface $token, string $table, int $id): bool
    {
        if ('tl_news' !== $table) {
            return true;
        }

        if (!$this->accessDecisionManager->decide($token, [ContaoNewsPermissions::USER_CAN_ACCESS_MODULE])) {
            return false;
        }

        $archiveId = $this->getArchiveId($id);

        return $archiveId
            && $this->accessDecisionManager->decide($token, [ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE], $archiveId);
    }

    private function getArchiveId(int $newsId): int
    {
        if (!isset($this->archives[$newsId])) {
            $this->archives[$newsId] = (int) $this->connection->fetchOne('SELECT pid FROM tl_news WHERE id=?', [$newsId]);
        }

        return $this->archives[$newsId];
    }
}
