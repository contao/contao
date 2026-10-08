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
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;

/**
 * @extends AbstractUserFactory<BackendUser>
 */
class BackendUserFactory extends AbstractUserFactory
{
    public const string INHERIT_GROUP = 'group';

    public const string INHERIT_EXTEND = 'extend';

    public const string INHERIT_CUSTOM = 'custom';

    private static array $permissionFields = ['modules', 'themes', 'elements', 'fields', 'frontendModules', 'pagemounts', 'alpty', 'filemounts', 'fop', 'forms', 'formp', 'imageSizes', 'amg', 'cud', 'alexf'];

    public function __construct(
        ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly ClockInterface $clock = new NativeClock(),
    ) {
        parent::__construct($framework);
    }

    public function create(array $data): BackendUser
    {
        $data = array_map(static fn ($v) => is_numeric($v) ? $v : StringUtil::deserialize($v), $data);

        // Inherit permissions
        $permissions = self::getPermissionFields();

        // Overwrite user permissions if only group permissions shall be inherited
        if (self::INHERIT_GROUP === ($data['inherit'] ?? null)) {
            foreach ($permissions as $field) {
                $data[$field] = [];
            }
        }

        $data['groups'] = \is_array($data['groups'] ?? null) ? array_filter($data['groups']) : [];

        // Merge permissions
        if ([] !== $data['groups']) {
            $inherit = \in_array($data['inherit'], [self::INHERIT_GROUP, self::INHERIT_EXTEND], true) ? $permissions : ['alexf'];
            $time = $this->clock->now()->getTimestamp();

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

        // Make sure pagemounts, filemounts, alexf and cud are set!
        foreach (['pagemounts', 'filemounts', 'alexf', 'cud'] as $field) {
            $data[$field] = \is_array($data[$field] ?? null) ? array_filter($data[$field]) : [];
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
