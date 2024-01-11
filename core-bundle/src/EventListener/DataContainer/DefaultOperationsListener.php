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

use Contao\Controller;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal
 */
#[AsHook('loadDataContainer', priority: 200)]
class DefaultOperationsListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(string $table): void
    {
        // Do not add default operations if a DCA was "loaded" that does not exist
        if (!isset($GLOBALS['TL_DCA'][$table])) {
            return;
        }

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
        if (!array_filter($dca, static fn ($v, $k) => isset($defaults[$k]) || (\is_string($v) && isset($defaults[$v])), ARRAY_FILTER_USE_BOTH)) {
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

        $isTreeMode = DataContainer::MODE_TREE === ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? null);
        $hasPtable = !empty($GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null);
        $ctable = $GLOBALS['TL_DCA'][$table]['config']['ctable'][0] ?? null;

        $canEdit = !($GLOBALS['TL_DCA'][$table]['config']['notEditable'] ?? false);
        $canCopy = !($GLOBALS['TL_DCA'][$table]['config']['closed'] ?? false) && !($GLOBALS['TL_DCA'][$table]['config']['notCopyable'] ?? false);
        $canSort = !($GLOBALS['TL_DCA'][$table]['config']['notSortable'] ?? false);
        $canDelete = !($GLOBALS['TL_DCA'][$table]['config']['notDeletable'] ?? false);

        if ($canEdit) {
            $operations += [
                'edit' => [
                    'href' => 'act=edit',
                    'icon' => 'edit.svg',
                    'button_callback' => $this->isGrantedCallback(UpdateAction::class, $table),
                ],
            ];
        }

        if ($ctable) {
            $this->framework->getAdapter(Controller::class)->loadDataContainer($ctable);

            if (DataContainer::MODE_TREE_EXTENDED !== ($GLOBALS['TL_DCA'][$ctable]['list']['sorting']['mode'] ?? null)) {
                $operations += [
                    'children' => [
                        'href' => 'table='.$ctable,
                        'icon' => 'children.svg',
                        'button_callback' => $this->accessChildrenCallback($ctable, $table),
                    ],
                ];
            }
        }

        if ($hasPtable || $isTreeMode) {
            if ($canCopy) {
                $operations['copy'] = [
                    'href' => 'act=paste&amp;mode=copy',
                    'icon' => 'copy.svg',
                    'attributes' => 'onclick="Backend.getScrollOffset()"',
                    'button_callback' => $this->isGrantedCallback(CreateAction::class, $table, ['sorting' => null]),
                ];

                if ($isTreeMode) {
                    $operations['copyChildren'] = [
                        'href' => 'act=paste&amp;mode=copy&amp;children=1',
                        'icon' => 'copychildren.svg',
                        'attributes' => 'onclick="Backend.getScrollOffset()"',
                        'button_callback' => $this->copyChildrenCallback($table),
                    ];
                }
            }

            if ($canSort) {
                $operations['cut'] = [
                    'href' => 'act=paste&amp;mode=cut',
                    'icon' => 'cut.svg',
                    'attributes' => 'onclick="Backend.getScrollOffset()"',
                    'button_callback' => $this->isGrantedCallback(UpdateAction::class, $table, ['sorting' => null]),
                ];
            }
        } elseif ($canCopy) {
            $operations['copy'] = [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
                'button_callback' => $this->isGrantedCallback(CreateAction::class, $table),
            ];
        }

        if ($canDelete) {
            $operations['delete'] = [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
                'button_callback' => $this->isGrantedCallback(DeleteAction::class, $table),
            ];
        }

        if ($canEdit && null !== ($toggleField = $this->getToggleField($table))) {
            $operations['toggle'] = [
                'href' => 'act=toggle&amp;field='.$toggleField,
                'icon' => 'visible.svg',
                'button_callback' => $this->isGrantedCallback(UpdateAction::class, $table),
            ];
        }

        return $operations + [
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ];
    }

    private function isGrantedCallback(string $actionClass, string $table, array|null $new = null): \Closure
    {
        return function (DataContainerOperation $operation) use ($actionClass, $table, $new): void {
            if (!$this->isGranted($actionClass, $table, $operation, $new)) {
                $operation->disable();
            }
        };
    }

    private function accessChildrenCallback(string $ctable, string $table): \Closure
    {
        return function (DataContainerOperation $operation) use ($ctable, $table): void {
            $data = [
                'pid' => $operation->getRecord()['pid'] ?? null,
            ];

            if ($GLOBALS['TL_DCA'][$ctable]['config']['dynamicPtable'] ?? false) {
                $data['ptable'] = $table;
            }

            $subject = new ReadAction($ctable, $data);

            if (!$this->security->isGranted(ContaoCorePermissions::DC_PREFIX.$ctable, $subject)) {
                $operation->disable();
            }
        };
    }

    private function copyChildrenCallback(string $table): \Closure
    {
        return function (DataContainerOperation $operation) use ($table): void {
            if (!$this->isGranted(CreateAction::class, $table, $operation, ['sorting' => null])) {
                $operation->disable();

                return;
            }

            $childCount = $this->connection->fetchOne(
                "SELECT COUNT(*) FROM $table WHERE pid=?",
                [(string) $operation->getRecord()['id']],
            );

            if ($childCount < 1) {
                $operation->disable();
            }
        };
    }

    /**
     * Finds the one and only toggle field in a DCA. Returns null if multiple fields can be toggled.
     */
    private function getToggleField(string $table): string|null
    {
        $field = null;

        foreach ($GLOBALS['TL_DCA'][$table]['fields'] ?? [] as $name => $config) {
            if (!($config['toggle'] ?? false) && !($config['reverseToggle'] ?? false)) {
                continue;
            }

            // More than one toggle field exists
            if (null !== $field) {
                return null;
            }

            $field = $name;
        }

        return $field;
    }

    private function isGranted(string $actionClass, string $table, DataContainerOperation $operation, array|null $new = null): bool
    {
        $subject = match ($actionClass) {
            CreateAction::class => new CreateAction($table, array_replace($operation->getRecord(), (array) $new)),
            UpdateAction::class => new UpdateAction($table, $operation->getRecord(), $new),
            DeleteAction::class => new DeleteAction($table, $operation->getRecord()),
            default => throw new \InvalidArgumentException(sprintf('Invalid action class "%s".', $actionClass)),
        };

        return $this->security->isGranted(ContaoCorePermissions::DC_PREFIX.$table, $subject);
    }
}
