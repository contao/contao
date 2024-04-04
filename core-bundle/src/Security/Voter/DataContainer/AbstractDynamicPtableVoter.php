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

abstract class AbstractDynamicPtableVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly Connection $connection)
    {
    }

    protected function hasAccess(TokenInterface $token, UpdateAction|CreateAction|ReadAction|DeleteAction $action): bool
    {
        if (
            !$action instanceof CreateAction
            && !$this->hasAccessToParent($token, $action->getCurrent())
        ) {
            return false;
        }

        // no need to check the "new" record if parent ID and table did not change
        if (
            $action instanceof ReadAction
            || $action instanceof DeleteAction
            || (!isset($action->getNew()['ptable']) && !isset($action->getNew()['pid']))
        ) {
            return true;
        }

        $record = array_replace(($action instanceof CreateAction ? [] : $action->getCurrent()), $action->getNew());

        return $this->hasAccessToParent($token, $record);
    }

    abstract protected function hasAccessToRecord(TokenInterface $token, string $table, int $id): bool;

    private function hasAccessToParent(TokenInterface $token, array $record): bool
    {
        if (!isset($record['ptable'], $record['pid'])) {
            return true;
        }

        if ($record['ptable'] !== $this->getTable()) {
            [$id, $table] = [$record['pid'], $record['ptable']];
        } else {
            [$id, $table] = $this->getParentTableAndId((int) $record['pid'], $record['ptable']);
        }

        return $this->hasAccessToRecord($token, $table, $id);

        //SELECT @pid:=pid, @ptable:=ptable FROM tl_content WHERE id=$id;
        //SET @query = CONCAT("SELECT *, '", @ptable, "' AS __parent_table FROM ", @ptable, ' WHERE id=?;');
        //PREPARE stmt FROM @query;
        //EXECUTE stmt USING @pid;
        //DEALLOCATE PREPARE stmt;
    }

    public function getParentTableAndId(int $id, string $table): array
    {
        // Limit to a nesting level of 10
        $records = $this->connection->fetchAllAssociative(
            "SELECT id, @pid:=pid AS pid, ptable FROM $table WHERE id=?" . str_repeat(" UNION SELECT id, @pid:=pid AS pid, ptable FROM $table WHERE id=@pid", 9),
            [$id]
        );

        // Trigger recursion in case our query returned exactly 10 IDs in which case we might have higher parent records
        if (10 === \count($records)) {
            $records = array_merge($records, $this->getParentTableAndId((int) end($records)['pid'], $table));
        }

        $top = end($records);

        return [$top['pid'], $top['ptable']];
    }
}
