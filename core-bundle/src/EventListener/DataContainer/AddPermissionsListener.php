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
use Contao\CoreBundle\Translation\Translator;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

class AddPermissionsListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Translator $translator,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsCallback('tl_image_size', 'fields.permissions.input_field')]
    public function generateFieldMarkup(DataContainer $dc): string
    {
        $widget = $this->getWidget($dc);

        return '<div class="widget">'.$widget->generateWithError().'</div>';
    }

    #[AsCallback('tl_image_size', 'config.onsubmit')]
    public function updateUserAndGroupPermissions(DataContainer $dc): void
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return;
        }

        $widget = $this->getWidget($dc);
        $widget->value = $request->get('permissions');
        $widget->validate();

        if ($widget->hasErrors()) {
            // TODO: Handle error case
            return;
        }

        $id = $dc->id;
        $value = StringUtil::deserialize($widget->value, true);

        ['group_ids' => $groupIds, 'user_ids' => $userIds] = array_reduce(
            $value,
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

        $target = $GLOBALS['TL_DCA'][$dc->table]['fields']['permissions']['eval']['target'];
        $groups = $this->connection->fetchAllAssociative(
            sprintf('SELECT id, %s FROM tl_user_group WHERE id IN (?)', $target),
            [$groupIds],
            [ArrayParameterType::INTEGER],
        );

        foreach ($groups as $group) {
            /** @var array $value */
            $value = StringUtil::deserialize($group[$target], true);

            if (!\in_array($id, $value, true)) {
                $value[] = $id;
            }

            $this->connection->update(
                'tl_user_group',
                [$target => serialize($value)],
                ['id' => $group['id']]
            );
        }

        // Update users

        $users = $this->connection->fetchAllAssociative(
            sprintf('SELECT id, %s FROM tl_user WHERE id IN (?)', $target),
            [$userIds],
            [ArrayParameterType::INTEGER],
        );

        foreach ($users as $user) {
            /** @var array $value */
            $value = StringUtil::deserialize($user[$target], true);

            if (!\in_array($id, $value, true)) {
                $value[] = $id;
            }

            $this->connection->update(
                'tl_user',
                [$target => serialize($value)],
                ['id' => $user['id']]
            );
        }
    }

    private function getWidget(DataContainer $dc): CheckBox
    {
        return new CheckBox([
            'id' => 'permissions',
            'name' => 'permissions',
            'label' => $this->translator->trans(sprintf('%s.permissions', $dc->table), [], 'contao_default'),
            'options' => $this->getUserAndGroupOptions($dc->table),
            'multiple' => true,
        ]);
    }

    private function getUserAndGroupOptions(string $table): array
    {
        $groups = $this->connection->fetchAllAssociative('SELECT id, name FROM tl_user_group ORDER BY name ASC');
        $users = $this->connection->fetchAllAssociative("SELECT id, name FROM tl_user WHERE inherit IN ('extend', 'custom') AND admin = false ORDER BY name ASC");

        $groupLabel = $this->translator->trans(sprintf('%s.group_permissions', $table), [], 'contao_default');
        $userLabel = $this->translator->trans(sprintf('%s.user_permissions', $table), [], 'contao_default');

        return [
            $groupLabel => array_map(static fn ($row) => ['label' => $row['name'], 'value' => 'g_'.$row['id']], $groups),
            $userLabel => array_map(static fn ($row) => ['label' => $row['name'], 'value' => 'u_'.$row['id']], $users),
        ];
    }
}
