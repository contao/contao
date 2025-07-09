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
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 */
#[AsHook('loadDataContainer', priority: 200)]
class DefaultOperationsListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
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
        $dca = $GLOBALS['TL_DCA'][$table]['list']['operations'] ?? null;

        if ([] === $dca) {
            return [];
        }

        $defaults = $this->getDefaults($table);

        if (!\is_array($dca)) {
            return $defaults;
        }

        $operations = [];

        // If none of the defined operations are name-only, we prepend the default operations.
        if (!array_filter($dca, static fn ($v, $k) => isset($defaults[$k]) || (\is_string($v) && isset($defaults[ltrim($v, '!')])), ARRAY_FILTER_USE_BOTH)) {
            $operations = $defaults;
        }

        foreach ($dca as $k => $v) {
            if ('-' === $v) {
                $operations[$k] = $v;
                continue;
            }

            if (\is_string($v) && ($key = ltrim($v, '!')) && isset($defaults[$key])) {
                $operations[$key] = $defaults[$key];

                if (str_starts_with($v, '!')) {
                    $operations[$key]['primary'] = true;
                }

                continue;
            }

            if (!\is_array($v)) {
                continue;
            }

            $operations[$k] = $v;
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
        $canCopy = !($GLOBALS['TL_DCA'][$table]['config']['closed'] ?? false) && !($GLOBALS['TL_DCA'][$table]['config']['notCreatable'] ?? false) && !($GLOBALS['TL_DCA'][$table]['config']['notCopyable'] ?? false);
        $canSort = !($GLOBALS['TL_DCA'][$table]['config']['notSortable'] ?? false);
        $canDelete = !($GLOBALS['TL_DCA'][$table]['config']['notDeletable'] ?? false);

        if ($canEdit) {
            $operations += [
                'edit' => [
                    'href' => 'act=edit',
                    'icon' => 'edit.svg',
                    'prefetch' => true,
                    'attributes' => 'data-contao--deeplink-target="primary"',
                    'button_callback' => $this->isGrantedCallback(UpdateAction::class, $table),
                    'primary' => true,
                    'showInHeader' => true,
                    'showIfDisabled' => true,
                ],
            ];
        }

        if ($ctable) {
            $this->framework->getAdapter(Controller::class)->loadDataContainer($ctable);

            if (DataContainer::MODE_TREE_EXTENDED !== ($GLOBALS['TL_DCA'][$ctable]['list']['sorting']['mode'] ?? null)) {
                $operations += [
                    'children' => [
                        'href' => 'table='.$ctable.($ctable === $table ? '&amp;ptable='.$table : ''),
                        'icon' => 'children.svg',
                        'prefetch' => true,
                        'attributes' => 'data-contao--deeplink-target="secondary"',
                        'button_callback' => $this->accessChildrenCallback($ctable, $table),
                        'primary' => true,
                        'showIfDisabled' => true,
                    ],
                ];
            }
        }

        if ($hasPtable || $isTreeMode) {
            if ($canCopy) {
                $operations['copy'] = [
                    'href' => 'act=paste&amp;mode=copy',
                    'method' => 'POST',
                    'icon' => 'copy.svg',
                    'attributes' => 'data-action="contao--scroll-offset#store"',
                    'button_callback' => $this->isGrantedCallback(CreateAction::class, $table, ['sorting' => null]),
                ];

                if ($isTreeMode) {
                    $operations['copyChildren'] = [
                        'href' => 'act=paste&amp;mode=copy&amp;children=1',
                        'method' => 'POST',
                        'icon' => 'copychildren.svg',
                        'attributes' => 'data-action="contao--scroll-offset#store"',
                        'button_callback' => $this->copyChildrenCallback($table),
                    ];
                }
            }

            if ($canSort) {
                $operations['cut'] = [
                    'href' => 'act=paste&amp;mode=cut',
                    'method' => 'POST',
                    'icon' => 'cut.svg',
                    'attributes' => 'data-action="contao--scroll-offset#store"',
                    'button_callback' => $this->isGrantedCallback(UpdateAction::class, $table, ['sorting' => null]),
                ];
            }
        } elseif ($canCopy) {
            $operations['copy'] = [
                'href' => 'act=copy',
                'method' => 'POST',
                'icon' => 'copy.svg',
                'button_callback' => $this->isGrantedCallback(CreateAction::class, $table),
            ];
        }

        if ($canDelete) {
            $operations['delete'] = [
                'href' => 'act=delete',
                'method' => 'DELETE',
                'icon' => 'delete.svg',
                'attributes' => 'data-action="contao--scroll-offset#store" onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false"',
                'button_callback' => $this->isGrantedCallback(DeleteAction::class, $table),
                'showIfDisabled' => true,
            ];
        }

        if ($canEdit && null !== ($toggleField = $this->getToggleField($table))) {
            $operations['toggle'] = [
                'href' => 'act=toggle&amp;field='.$toggleField,
                'icon' => 'visible.svg',
                'showInHeader' => (bool) $ctable,
                'button_callback' => $this->toggleCallback($table, $toggleField),
                'primary' => true,
            ];
        }

        return $operations + [
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
                'method' => 'GET',
                'prefetch' => false,
            ],
        ];
    }

    private function isGrantedCallback(string $actionClass, string $table, array|null $new = null): \Closure
    {
        return function (DataContainerOperation $operation) use ($actionClass, $table, $new): void {
            $accessDecision = $this->isGranted($actionClass, $table, $operation, $new);

            if (
                false === $accessDecision
                || ($accessDecision instanceof AccessDecision && !$accessDecision->isGranted)
            ) {
                $operation->disable($this->getAccessDeniedMessage($accessDecision));
            }
        };
    }

    private function accessChildrenCallback(string $ctable, string $table): \Closure
    {
        return function (DataContainerOperation $operation) use ($ctable, $table): void {
            $data = [
                'pid' => $operation->getRecord()['id'] ?? null,
            ];

            if ($GLOBALS['TL_DCA'][$ctable]['config']['dynamicPtable'] ?? false) {
                $data['ptable'] = $table;
            }

            $subject = new ReadAction($ctable, $data);
            $accessDecision = class_exists(AccessDecision::class) ? new AccessDecision() : null;

            if (!$this->authorizationChecker->isGranted(ContaoCorePermissions::DC_PREFIX.$ctable, $subject, $accessDecision)) {
                if ($ctable === $table) {
                    $operation->hide();
                } else {
                    $operation->disable($this->getAccessDeniedMessage($accessDecision));
                }
            }
        };
    }

    private function copyChildrenCallback(string $table): \Closure
    {
        return function (DataContainerOperation $operation) use ($table): void {
            $accessDecision = $this->isGranted(CreateAction::class, $table, $operation, ['sorting' => null]);

            if (false === $accessDecision || ($accessDecision instanceof AccessDecision && !$accessDecision->isGranted)) {
                $operation->disable($this->getAccessDeniedMessage($accessDecision));

                return;
            }

            $childCount = $this->connection->fetchOne(
                "SELECT COUNT(*) FROM $table WHERE pid = ?",
                [(string) $operation->getRecord()['id']],
            );

            if ($childCount < 1) {
                $operation->disable($this->getAccessDeniedMessage($accessDecision));
            }
        };
    }

    private function toggleCallback(string $table, string $toggleField): \Closure
    {
        return function (DataContainerOperation $operation) use ($toggleField, $table): void {
            $new = [$toggleField => !($operation['record'][$toggleField] ?? false)];
            $accessDecision = $this->isGranted(UpdateAction::class, $table, $operation, $new);

            if (false === $accessDecision || ($accessDecision instanceof AccessDecision && !$accessDecision->isGranted)) {
                // Do not use DataContainerOperation::disable() because it would not show the
                // actual state
                unset($operation['route'], $operation['href']);
                $operation['title'] = $this->getAccessDeniedMessage($accessDecision);
            }
        };
    }

    /**
     * Finds the one and only toggle field in a DCA. Returns null if multiple fields
     * can be toggled.
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

    private function isGranted(string $actionClass, string $table, DataContainerOperation $operation, array|null $new = null): AccessDecision|bool
    {
        $subject = match ($actionClass) {
            CreateAction::class => new CreateAction($table, array_replace($operation->getRecord(), (array) $new)),
            UpdateAction::class => new UpdateAction($table, $operation->getRecord(), $new),
            DeleteAction::class => new DeleteAction($table, $operation->getRecord()),
            default => throw new \InvalidArgumentException(\sprintf('Invalid action class "%s".', $actionClass)),
        };

        // TODO: class always exists when we require at least Symfony 7.3+
        $accessDecision = class_exists(AccessDecision::class) ? new AccessDecision() : null;

        $isGranted = $this->authorizationChecker->isGranted(ContaoCorePermissions::DC_PREFIX.$table, $subject, $accessDecision);

        return $accessDecision ?? $isGranted;
    }

    /**
     * Same as AccessDecision::getMessage() but without the non-translated prefix.
     */
    private function getAccessDeniedMessage(AccessDecision|bool|null $accessDecision): string
    {
        if (!$accessDecision instanceof AccessDecision || $accessDecision->isGranted) {
            return '';
        }

        $message = '';

        if ($accessDecision->votes) {
            foreach ($accessDecision->votes as $vote) {
                if (VoterInterface::ACCESS_DENIED !== $vote->result) {
                    continue;
                }

                foreach ($vote->reasons as $reason) {
                    $message .= ' '.$reason;
                }
            }
        }

        return trim($message);
    }
}
