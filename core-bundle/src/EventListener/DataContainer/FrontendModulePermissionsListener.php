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
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

class FrontendModulePermissionsListener
{
    public function __construct(
        private readonly Security $security,
        private readonly Connection $connection,
    ) {
    }

    #[AsCallback('tl_module', 'config.onload')]
    public function setDefaultType(): void
    {
        $user = $this->security->getUser();

        if (!($user instanceof BackendUser)) {
            return;
        }

        if (!empty($user->frontendModules) && !\in_array($GLOBALS['TL_DCA']['tl_module']['fields']['type']['sql']['default'] ?? null, $user->frontendModules, true)) {
            $GLOBALS['TL_DCA']['tl_module']['fields']['type']['default'] = $user->frontendModules[0];
        }
    }

    #[AsCallback(table: 'tl_user_group', target: 'fields.frontendModules.options')]
    #[AsCallback(table: 'tl_user', target: 'fields.frontendModules.options')]
    public function frontendModuleOptions(): array
    {
        return array_map('array_keys', $GLOBALS['FE_MOD']);
    }

    #[AsCallback('tl_content', 'fields.module.options')]
    public function allowedFrontendModuleOptions(): array
    {
        $options = [];
        $modules = $this->connection->fetchAllAssociative('SELECT m.id, m.name, m.type, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id ORDER BY t.name, m.name');

        foreach ($modules as $module) {
            if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULE_TYPE, $module['type'])) {
                continue;
            }

            $options[$module['theme']][$module['id']] = sprintf('%s (ID %s)', $module['name'], $module['id']);
        }

        return $options;
    }
}
