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

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Service\ResetInterface;

abstract class AbstractDynamicPtableVoter extends AbstractDataContainerVoter implements ResetInterface
{
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

        if ($record['ptable'] === $this->getTable()) {
            $record = $this->getParentTableAndId((int) $record['pid']);
        }

        return $this->hasAccessToRecord($token, $record['ptable'], $record['pid']);
    }

    private function getParentTableAndId(int $id): array
    {
        if (isset($this->parents[$id])) {
            return $this->parents[$id];
        }

        $table = $this->getTable();

        // Limit to a nesting level of 10
        $records = $this->connection->fetchAllAssociative(
            "SELECT id, @pid:=pid AS pid, ptable FROM $table WHERE id=?".str_repeat(" UNION SELECT id, @pid:=pid AS pid, ptable FROM $table WHERE id=@pid", 9),
            [$id],
        );

        // Trigger recursion in case our query returned exactly 10 records in which case
        // we might have higher parent records
        if (10 === \count($records)) {
            $records = array_merge($records, $this->getParentTableAndId((int) end($records)['pid']));
        }

        return $this->parents[$id] = end($records);
    }
}
