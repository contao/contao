<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\User;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * @extends AbstractUserFactory<BackendUser>
 */
class BackendUserFactory extends AbstractUserFactory
{
    private static array $permissionFields = ['modules', 'themes', 'elements', 'fields', 'frontendModules', 'pagemounts', 'alpty', 'filemounts', 'fop', 'forms', 'formp', 'imageSizes', 'amg', 'cud', 'alxef'];

    public function __construct(
        ContaoFramework $framework,
        private readonly Connection $connection,
    ) {
        parent::__construct($framework);
    }

    public function create(array $data): BackendUser
    {
        $data = array_map(static fn ($v) => is_numeric($v) ? $v : StringUtil::deserialize($v), $data);

        // Inherit permissions
        $permissions = self::getPermissionFields();

        // Overwrite user permissions if only group permissions shall be inherited
        if ('group' === ($data['inherit'] ?? null)) {
            foreach ($permissions as $field) {
                $data[$field] = [];
            }
        }

        // Make sure pagemounts, filemounts, alexf and cud are set!
        foreach (['pagemounts', 'filemounts', 'alexf', 'cud', 'groups'] as $field) {
            $data[$field] = \is_array($data[$field] ?? null) ? array_filter($data[$field]) : [];
        }

        // Merge permissions
        if ([] !== $data['groups']) {
            $inherit = \in_array($data['inherit'], ['group', 'extend'], true) ? $permissions : ['alexf'];
            $time = Date::floorToMinute();

            $groups = $this->connection->fetchAllAssociative(
                "SELECT * FROM tl_user_group WHERE id IN (?) AND disable=0 AND (start='' OR start<=$time) AND (stop='' OR stop>$time)",
                [$data['groups']],
                [ArrayParameterType::INTEGER],
            );

            foreach ($groups as $group) {
                foreach ($inherit as $field) {
                    $value = StringUtil::deserialize($group[$field] ?? null, true);

                    // The new page/file picker can return integers instead of arrays, so use empty()
                    // instead of is_array() and StringUtil::deserialize(true) here
                    if (!empty($value)) {
                        $data[$field] = array_unique(array_merge((array) ($data[$field] ?? null), $value));
                    }
                }
            }
        }

        if (!($data['admin'] ?? null)) {
            // Convert the file mounts into paths
            if (!empty($data['filemounts'])) {
                $data['filemounts'] = $this->connection->fetchFirstColumn(
                    'SELECT path FROM tl_files WHERE uuid IN (?)',
                    [$data['filemounts']],
                    [ArrayParameterType::STRING],
                );
            }

            // Hide the "admin" field if the user is not an admin (see #184)
            if (false !== ($index = array_search('tl_user::admin', $data['alexf'], true))) {
                unset($data['alexf'][$index]);
            }
        }

        $roles = ['ROLE_USER'];

        if ($data['admin'] ?? null) {
            $roles = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH', 'ROLE_ALLOWED_TO_SWITCH_MEMBER'];
        } elseif (!empty($data['amg']) && \is_array($data['amg'])) {
            $roles = ['ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH_MEMBER'];
        }

        return new BackendUser($data, $roles);
    }

    public function supportsClass(string $className): bool
    {
        return BackendUser::class === $className;
    }

    public function getTable(): string
    {
        return 'tl_user';
    }

    public static function registerPermissionField(string $field): void
    {
        self::$permissionFields[] = $field;
    }

    /**
     * @internal
     *
     * @return array<string>
     */
    public static function getPermissionFields(): array
    {
        $fields = self::$permissionFields;

        // HOOK: Take custom permissions
        if (!empty($GLOBALS['TL_PERMISSIONS'] ?? null) && \is_array($GLOBALS['TL_PERMISSIONS'])) {
            trigger_deprecation('contao/core-bundle', '6.1', 'Using $GLOBALS[\'TL_PERMISSIONS\'] is deprecated, register fields using %s::addPermissionField', self::class);

            $fields = array_merge($fields, $GLOBALS['TL_PERMISSIONS']);
        }

        return array_unique($fields);
    }
}
