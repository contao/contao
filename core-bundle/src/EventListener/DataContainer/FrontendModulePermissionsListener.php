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

use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Image;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class FrontendModulePermissionsListener
{
    public function __construct(
        private readonly Security $security,
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsCallback('tl_module', 'config.onload')]
    public function filterFrontendModules(): void
    {
        $user = $this->security->getUser();
        $request = $this->requestStack->getCurrentRequest();

        if (!$user instanceof BackendUser || null === $request) {
            return;
        }

        if ($user->isAdmin) {
            return;
        }

        if (empty($user->frontendModules)) {
            $GLOBALS['TL_DCA']['tl_module']['config']['closed'] = true;
            $GLOBALS['TL_DCA']['tl_module']['config']['notEditable'] = true;
        } elseif (!\in_array($GLOBALS['TL_DCA']['tl_module']['fields']['type']['sql']['default'] ?? null, $user->frontendModules, true)) {
            $GLOBALS['TL_DCA']['tl_module']['fields']['type']['default'] = $user->frontendModules[0];
        }

        $session = $this->requestStack->getSession();

        // Prevent editing front end modules with not allowed types
        if ('edit' === $request->query->get('act') || 'toggle' === $request->query->get('act') || 'delete' === $request->query->get('act') || ('paste' === $request->query->get('act') && 'copy' === $request->query->get('mode'))) {
            $module = $this->connection->fetchAssociative('SELECT type FROM tl_module WHERE id=?', [$request->query->get('id')]);

            if (\count($module) > 0 && !\in_array($module['type'], $user->frontendModules, true)) {
                throw new AccessDeniedException(sprintf('Not enough permissions to modify front end modules of type "%s".', $module['type']));
            }
        }

        // Prevent editing front end modules with not allowed types
        if ('editAll' === $request->query->get('act') || 'overrideAll' === $request->query->get('act') || 'deleteAll' === $request->query->get('act')) {
            $sessionData = $session->all();

            if (!empty($sessionData['CURRENT']['IDS']) && \is_array($sessionData['CURRENT']['IDS'])) {
                if (empty($user->frontendModules)) {
                    $sessionData['CURRENT']['IDS'] = [];
                } else {
                    $moduleIds = $this->connection->fetchFirstColumn(
                        'SELECT id FROM tl_module WHERE id IN (?) AND type IN (?)', [
                        array_map('\intval', $sessionData['CURRENT']['IDS']),
                        ...$user->frontendModules,
                    ]);

                    $sessionData['CURRENT']['IDS'] = array_intersect($sessionData['CURRENT']['IDS'], $moduleIds);
                }

                $session->replace($sessionData);
            }
        }

        // Prevent copying front end modules with not allowed types
        if ('copyAll' === $request->query->get('act')) {
            $sessionData = $session->all();

            if (!empty($sessionData['CLIPBOARD']['tl_module']['id']) && \is_array($sessionData['CLIPBOARD']['tl_module']['id'])) {
                if (empty($user->frontendModules)) {
                    $sessionData['CLIPBOARD']['tl_module']['id'] = [];
                } else {
                    $moduleIds = $this->connection->fetchFirstColumn(
                        'SELECT id, type FROM tl_module WHERE id IN (?) AND type IN (?)', [
                        array_map('\intval', $sessionData['CLIPBOARD']['tl_module']['id']),
                        ...$user->frontendModules,
                    ]);

                    $sessionData['CLIPBOARD']['tl_module']['id'] = array_intersect($sessionData['CLIPBOARD']['tl_module']['id'], $moduleIds);
                }

                $session->replace($sessionData);
            }
        }
    }

    #[AsCallback('tl_content', 'fields.module.options')]
    public function frontendModuleOptions(): array
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

    #[AsCallback('tl_module', 'list.operations.edit.button')]
    #[AsCallback('tl_module', 'list.operations.copy.button')]
    #[AsCallback('tl_module', 'list.operations.cut.button')]
    public function disableButton(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULE_TYPE, $row['type']) ? '<a href="'.Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
    }

    #[AsCallback('tl_module', 'list.operations.delete.button')]
    public function disableDeleteButton($row, $href, $label, $title, $icon, $attributes)
    {
        // Disable the button if the element type is not allowed
        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULE_TYPE, $row['type']))
        {
            return Image::getHtml(str_replace('.svg', '--disabled.svg', $icon)) . ' ';
        }


        return '<a href="' . Backend::addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ';
    }

}
