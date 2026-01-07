<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
#[AsHook('loadDataContainer', priority: -64)]
class UserRootListener implements ResetInterface
{
    /**
     * @var array<string, array<array>>
     */
    private array $recordCache = [];

    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(string $table): void
    {
        if (!is_a(DataContainer::getDriverForTable($table), DC_Table::class, true)) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $GLOBALS['TL_DCA'][$table]['config']['onload_callback'][] = fn () => $this->filterRecords($table, $user);
            $GLOBALS['TL_DCA'][$table]['config']['oncreate_callback'][] = fn ($ignore, $insertId) => $this->adjustPermissions($table, (int) $insertId, $user);
            $GLOBALS['TL_DCA'][$table]['config']['oncopy_callback'][] = fn ($insertId) => $this->adjustPermissions($table, (int) $insertId, $user);
        }

        $GLOBALS['TL_DCA'][$table]['config']['onload_callback'][] = fn () => $this->injectPermissionField($table);
    }

    public function reset(): void
    {
        $this->recordCache = [];
    }

    private function filterRecords(string $table, BackendUser $user): void
    {
        if (!isset($GLOBALS['TL_DCA'][$table]['config']['userRoot'])) {
            return;
        }

        $rootField = $GLOBALS['TL_DCA'][$table]['config']['userRoot'];
        $root = $user->{$rootField};

        if (empty($root) || !\is_array($root)) {
            $root = [0];
        }

        $GLOBALS['TL_DCA'][$table]['list']['sorting']['root'] = $root;
    }

    private function adjustPermissions(string $table, int $insertId, BackendUser $user): void
    {
        if (!isset($GLOBALS['TL_DCA'][$table]['config']['userRoot'])) {
            return;
        }

        $rootField = $GLOBALS['TL_DCA'][$table]['config']['userRoot'];
        $root = $user->{$rootField};

        if (empty($root) || !\is_array($root)) {
            $root = [0];
        }

        // The new element is enabled already
        if (\in_array($insertId, $root, false)) {
            return;
        }

        /** @var AttributeBagInterface $sessionBag */
        $sessionBag = $this->requestStack->getSession()->getBag('contao_backend');
        $newRecords = $sessionBag->get('new_records');

        if (!\is_array($newRecords[$table] ?? null) || !\in_array($insertId, $newRecords[$table], false)) {
            return;
        }

        // Add the permissions on group level
        if ('custom' !== $user->inherit) {
            $groups = $this->connection->fetchAllAssociative(
                'SELECT * FROM tl_user_group WHERE id IN (?)',
                [$user->groups],
                [ArrayParameterType::INTEGER],
            );

            foreach ($groups as $group) {
                $this->addNewPermission('tl_user_group', $group, $rootField, $insertId);
            }
        }

        // Add the permissions on user level
        if ('group' !== $user->inherit) {
            $this->addNewPermission('tl_user', $user->getData(), $rootField, $insertId);
        }

        // Add the new element to the user object
        $root[] = $insertId;
        $user->{$rootField} = $root;
    }

    private function addNewPermission(string $table, array $record, string $rootField, int $insertId): void
    {
        $root = (array) StringUtil::deserialize($record[$rootField], true);
        $root[] = $insertId;
        $new = [$rootField => serialize($root)];

        $this->connection->update($table, $new, ['id' => $record['id']]);
    }

    private function injectPermissionField(string $table): void
    {
        if (!isset($GLOBALS['TL_DCA'][$table]['config']['userRoot'])) {
            return;
        }

        $rootField = $GLOBALS['TL_DCA'][$table]['config']['userRoot'];
        $canEditUsers = $this->canEdit('user', 'tl_user', $rootField);
        $canEditGroups = $this->canEdit('group', 'tl_user_group', $rootField);

        if (!$canEditUsers && !$canEditGroups) {
            return;
        }

        if (isset($GLOBALS['TL_LANG'][$table]['_permissions'])) {
            $label = &$GLOBALS['TL_LANG'][$table]['_permissions'];
        } else {
            $label = &$GLOBALS['TL_LANG']['DCA']['permissions'];
        }

        if (!isset($GLOBALS['TL_LANG'][$table]['permissions_legend'])) {
            $GLOBALS['TL_LANG'][$table]['permissions_legend'] = &$GLOBALS['TL_LANG']['DCA']['permissions_legend'];
        }

        $GLOBALS['TL_DCA'][$table]['fields']['_permissions'] = [
            'label' => $label,
            'inputType' => 'checkbox',
            'options_callback' => fn () => $this->getUserAndGroupOptions($label, $canEditUsers, $canEditGroups),
            'eval' => ['multiple' => true, 'doNotSaveEmpty' => true],
            'load_callback' => [fn ($value, DataContainer $dc) => $this->loadPermissions((int) $dc->id, $rootField, $canEditUsers, $canEditGroups)],
            'save_callback' => [fn ($value, DataContainer $dc) => $this->savePermissions((int) $dc->id, $rootField, $value, $canEditUsers, $canEditGroups)],
        ];

        $GLOBALS['TL_DCA'][$table]['config']['onpalette_callback'][] = static fn (string $palette): string => PaletteManipulator::create()
            ->addLegend('permissions_legend', null, PaletteManipulator::POSITION_APPEND)
            ->addField('_permissions', 'permissions_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToString($palette)
        ;
    }

    private function canEdit(string $module, string $table, string $rootField): bool
    {
        return $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, $module)
            && $this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $table.'::'.$rootField);
    }

    private function loadPermissions(int $recordId, string $rootField, bool $canEditUsers, bool $canEditGroups): array
    {
        $users = $this->fetchRecords('tl_user', 'u', $rootField, $canEditUsers);
        $groups = $this->fetchRecords('tl_user_group', 'g', $rootField, $canEditGroups);
        $filter = static fn (string|null $value): bool => \in_array($recordId, StringUtil::deserialize($value, true), false);

        return [...array_keys(array_filter($users, $filter)), ...array_keys(array_filter($groups, $filter))];
    }

    private function savePermissions(int $recordId, string $rootField, mixed $value, bool $canEditUsers, bool $canEditGroups): void
    {
        $value = StringUtil::deserialize($value, true);

        $this->updatePermission('tl_user', 'u', $rootField, $canEditUsers, $value, $recordId);
        $this->updatePermission('tl_user_group', 'g', $rootField, $canEditGroups, $value, $recordId);
    }

    private function updatePermission(string $table, string $prefix, string $rootField, bool $canEdit, array $selection, int $recordId): void
    {
        $records = $this->fetchRecords($table, $prefix, null, $canEdit);

        foreach ($records as $record) {
            $root = (array) StringUtil::deserialize($record[$rootField], true);
            $isEnabled = \in_array($prefix.$record['id'], $selection, true);
            $isActive = \in_array($record['id'], $root, false);

            // Permission is already correct
            if (($isEnabled && $isActive) || (!$isEnabled && !$isActive)) {
                continue;
            }

            if ($isEnabled) {
                $root[] = $recordId;
            } else {
                $root = array_diff($root, [$recordId]);
            }

            $new = [$rootField => serialize($root)];

            if (!$this->security->isGranted(ContaoCorePermissions::DC_PREFIX.$table, new UpdateAction($table, $record, $new))) {
                return;
            }

            $this->connection->update($table, $new, ['id' => $record['id']]);
        }
    }

    private function getUserAndGroupOptions(array $label, bool $canEditUsers, bool $canEditGroups): array
    {
        $users = $this->fetchRecords('tl_user', 'u', 'name', $canEditUsers);
        $groups = $this->fetchRecords('tl_user_group', 'g', 'name', $canEditGroups);

        return [
            ($label['groups'] ?? 'groups') => $groups,
            ($label['users'] ?? 'users') => $users,
        ];
    }

    private function fetchRecords(string $table, string $prefix, string|null $field, bool $canEdit): array
    {
        if (!$canEdit) {
            return [];
        }

        if (isset($this->recordCache[$table])) {
            $results = $this->recordCache[$table];
        } else {
            $where = match ($table) {
                'tl_user' => "WHERE inherit IN ('extend', 'custom') AND admin = false",
                default => '',
            };

            $results = $this->connection->fetchAllAssociative("SELECT * FROM $table $where ORDER BY name");
            $results = array_filter($results, fn (array $data) => $this->security->isGranted(ContaoCorePermissions::DC_PREFIX.$table, new UpdateAction($table, $data)));

            $this->recordCache[$table] = $results;
        }

        if (null === $field) {
            return $results;
        }

        $records = [];

        foreach ($results as $row) {
            $records[$prefix.$row['id']] = $row[$field];
        }

        return $records;
    }
}
