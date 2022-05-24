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

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\DataContainerSubject;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\DataContainer;
use Symfony\Component\Security\Core\Security;

/**
 * @internal
 *
 * @Hook("loadDataContainer", priority=200)
 */
class DefaultOperationsListener
{
    public function __construct(private Security $security)
    {
    }

    public function __invoke(string $table): void
    {
        $GLOBALS['TL_DCA'][$table]['list']['operations'] = $this->getForTable($table);
    }

    private function getForTable(string $table): array
    {
        $defaults = $this->getDefaults($table);
        $dca = $GLOBALS['TL_DCA'][$table]['list']['operations'] ?? null;

        if (!\is_array($dca)) {
            return $defaults;
        }

        $operations = [];

        // If none of the defined operations are name-only, we append the operations to the defaults.
        if (empty(array_filter($dca, static fn ($v) => \is_string($v) && isset($defaults[$v])))) {
            $operations = $defaults;
        }

        foreach ($dca as $k => $v) {
            if (\is_string($v) && isset($defaults[$v])) {
                $operations[$v] = $defaults[$v];
                continue;
            }

            $operations[$k] = \is_array($v) ? $v : [$v];
        }

        return $operations;
    }

    private function getDefaults(string $table): array
    {
        $operations = [];

        $isTreeMode = ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? null) === DataContainer::MODE_TREE;
        $hasPtable = !empty($GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null);
        $ctable = $GLOBALS['TL_DCA'][$table]['config']['ctable'][0] ?? null;

        if ($ctable) {
            $operations += [
                'edit' => [
                    'href' => 'table='.$ctable,
                    'icon' => 'edit.svg',
                ],
                'editheader' => [
                    'href' => 'act=edit',
                    'icon' => 'header.svg',
                    'button_callback' => $this->generateCallback(ContaoCorePermissions::DC_ACTION_EDIT),
                ],
            ];
        } else {
            $operations['edit'] = [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
                'button_callback' => $this->generateCallback(ContaoCorePermissions::DC_ACTION_EDIT),
            ];
        }

        if ($hasPtable || $isTreeMode) {
            $operations['copy'] = [
                'href' => 'act=paste&amp;mode=copy',
                'icon' => 'copy.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => $this->generateCallback(ContaoCorePermissions::DC_ACTION_COPY),
            ];

            if ($isTreeMode) {
                // TODO: how to we check permissions for that?
                $operations['copyChilds'] = [
                    'href' => 'act=paste&amp;mode=copy&amp;childs=1',
                    'icon' => 'copychilds.svg',
                    'attributes' => 'onclick="Backend.getScrollOffset()"',
                ];
            }

            $operations['cut'] = [
                'href' => 'act=paste&amp;mode=cut',
                'icon' => 'cut.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => $this->generateCallback(ContaoCorePermissions::DC_ACTION_MOVE),
            ];
        } else {
            $operations['copy'] = [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
                'button_callback' => $this->generateCallback(ContaoCorePermissions::DC_ACTION_COPY),
            ];
        }

        $operations += [
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
                'button_callback' => $this->generateCallback(ContaoCorePermissions::DC_ACTION_DELETE),
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ];

        return $operations;
    }

    private function generateCallback(string $attribute): \Closure
    {
        return function (DataContainerOperation $config) use ($attribute): void {
            $subject = new DataContainerSubject($config->getDataContainer()->table, rawurldecode((string) $config->getRecord()['id']));

            if (!$this->security->isGranted($attribute, $subject)) {
                unset($config['route'], $config['href']);

                if (isset($config['icon'])) {
                    $config['icon'] = preg_replace('/(\.svg)$/i', '_.svg', $config['icon']);
                }
            }
        };
    }
}
