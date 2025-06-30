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

use Contao\CoreBundle\DataContainer\DynamicPtableTrait;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Service\ResetInterface;

abstract class AbstractDynamicPtableVoter extends AbstractDataContainerVoter implements ResetInterface
{
    use DynamicPtableTrait;

    private array $parents = [];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function reset(): void
    {
        $this->parents = [];
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if (!$action instanceof CreateAction && !$this->hasAccessToParent($token, $action->getCurrent())) {
            return false;
        }

        // No need to check the "new" record if the parent ID and table did not change
        if (
            $action instanceof ReadAction
            || $action instanceof DeleteAction
            || (!isset($action->getNew()['ptable']) && !isset($action->getNew()['pid']))
        ) {
            return true;
        }

        $record = array_replace($action instanceof CreateAction ? [] : $action->getCurrent(), $action->getNew());

        return $this->hasAccessToParent($token, $record);
    }

    abstract protected function hasAccessToRecord(TokenInterface $token, string $table, int $id): bool;

    private function hasAccessToParent(TokenInterface $token, array $record): bool
    {
        if (!isset($record['ptable'], $record['pid'])) {
            return true;
        }

        [$table, $id] = [$record['ptable'], (int) $record['pid']];

        if ($record['ptable'] === $this->getTable()) {
            try {
                [$table, $id] = $this->fetchParentTableAndId($id);
            } catch (\RuntimeException) {
                return false;
            }
        }

        return $this->hasAccessToRecord($token, $table, $id);
    }

    /**
     * Recursively finds the ptable and pid of a nested record in the current table.
     * - for articles, it returns `tl_article` and the ID of the current article.
     * - for news, it returns `tl_news` and the ID of the current news.
     * - for calendars, it returns `tl_calendar_events` and the ID of the current event.
     *
     * @return array{0: string, 1: int}
     */
    private function fetchParentTableAndId(int $id): array
    {
        return $this->parents[$id] ?? ($this->parents[$id] = $this->getParentTableAndId($this->connection, $this->getTable(), $id));
    }
}
