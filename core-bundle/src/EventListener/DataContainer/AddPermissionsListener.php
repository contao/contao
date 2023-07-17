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

use Contao\CheckBox;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

class AddPermissionsListener
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    #[AsCallback('tl_image_size', 'fields.permissions.input_field')]
    public function __invoke(DataContainer $dc): string
    {
        $widget = new CheckBox([
            'id' => 'permissions',
            'name' => 'permissions',
            'options' => $this->getUserAndGroupOptions(),
            'multiple' => true,
        ]);

        // TODO: Add

        $widget->validate();

        if (\is_array($widget->value)) {
            $this->updateUserAndGroupPermissions($dc->id, $widget->value);
        }

        return '<div class="widget w50">'.$widget->generateWithError().'</div>';
    }

    private function getUserAndGroupOptions(): array
    {
        $groups = $this->connection
            ->prepare('SELECT id, name FROM tl_user_group ORDER BY name ASC')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $users = $this->connection
            ->prepare("SELECT id, name FROM tl_user WHERE inherit IN ('extend', 'custom') AND admin = false ORDER BY name ASC")
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        return [
            'groups' => array_map(static fn ($row) => ['label' => $row['name'], 'value' => 'g_'.$row['id']], $groups),
            'users' => array_map(static fn ($row) => ['label' => $row['name'], 'value' => 'u_'.$row['id']], $users),
        ];
    }

    private function updateUserAndGroupPermissions(string $id, array $values): void
    {
        ['group_ids' => $groupIds, 'user_ids' => $userIds] = array_reduce(
            $values,
            static function ($carry, $item) {
                if (str_starts_with($item, 'g_')) {
                    $carry['group_ids'][] = (int) ltrim($item, 'g_');
                }

                if (str_starts_with($item, 'u_')) {
                    $carry['user_ids'][] = (int) ltrim($item, 'u_');
                }

                return $carry;
            },
            ['group_ids' => [], 'user_ids' => []]
        );

        // Update groups

        $groups = $this->connection->fetchAllAssociative(
            'SELECT id, imageSizes FROM tl_user_group WHERE id IN (?)',
            [$groupIds],
            [ArrayParameterType::INTEGER],
        );

        foreach ($groups as $group) {
            /** @var array $imageSizes */
            $imageSizes = StringUtil::deserialize($group['imageSizes'], true);

            if (!in_array($id, $imageSizes, true)) {
                $imageSizes[] = $id;
            }

            $this->connection->update(
                'tl_user_group',
                ['imageSizes' => serialize($imageSizes)],
                ['id' => $group['id']]
            );
        }

        // Update users

        $users = $this->connection->fetchAllAssociative(
            'SELECT id, imageSizes FROM tl_user WHERE id IN (?)',
            [$userIds],
            [ArrayParameterType::INTEGER],
        );

        foreach ($users as $user) {
            /** @var array $imageSizes */
            $imageSizes = StringUtil::deserialize($user['imageSizes'], true);

            if (!in_array($id, $imageSizes, true)) {
                $imageSizes[] = $id;
            }

            $this->connection->update(
                'tl_user',
                ['imageSizes' => serialize($imageSizes)],
                ['id' => $user['id']]
            );
        }
    }
}
