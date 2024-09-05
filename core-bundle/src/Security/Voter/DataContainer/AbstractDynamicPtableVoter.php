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

        return $this->hasAccessToRecord($token, $record['ptable'], (int) $record['pid']);
    }

    private function getParentTableAndId(int $id): array
    {
        if (isset($this->parents[$id])) {
            return $this->parents[$id];
        }

        $table = $this->getTable();

        // Limit to a nesting level of 10
        $records = $this->connection->fetchAllAssociative(
            "SELECT id, @pid:=pid AS pid, ptable FROM $table WHERE id=:id".str_repeat(" UNION SELECT id, @pid:=pid AS pid, ptable FROM $table WHERE id=@pid AND ptable=:ptable", 9),
            ['id' => $id, 'ptable' => $table],
        );

        if (!$records) {
            throw new \RuntimeException(\sprintf('Parent record of %s.%s not found', $table, $id));
        }

        $last = end($records);

        // If the given $id is the child of a record where ptable!=$table (e.g. only one
        // element nested), $records will only have one result, and we can directly use it.
        if ($last['ptable'] !== $table) {
            return $this->parents[$id] = $last;
        }

        // Trigger recursion in case our query returned exactly 10 records in which case
        // we might have higher parent records
        if (10 === \count($records)) {
            return $this->getParentTableAndId((int) $last['pid']);
        }

        // If we have more than 1 but less than 10 results, the last result in our array
        // must be the first nested element, and its parent is what we are looking for.
        return $this->parents[$id] = $this->connection->fetchAssociative(
            "SELECT id, pid, ptable FROM $table WHERE id=?",
            [$last['pid']],
        );
    }
}
