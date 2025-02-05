<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\DcaLoader;
use Contao\Input;
use Contao\System;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DcaUrlAnalyzer
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
        private readonly Security $securityHelper,
        private readonly RouterInterface $router,
        private readonly TranslatorBagInterface&TranslatorInterface $translator,
        private readonly RecordLabeler $recordLabeler,
        private readonly DcaRequestSwitcher $dcaRequestSwitcher,
    ) {
    }

    /**
     * @return array{string|null, int|null}
     */
    public function getCurrentTableId(Request|string|null $request = null): array
    {
        $request = $this->resolveRequest($request);

        return $this->dcaRequestSwitcher->runWithRequest($request, fn (): array => $this->findTableAndId());
    }

    /**
     * @return list<array{url: string, label: string}>
     */
    public function getTrail(Request|string|null $request = null): array
    {
        return $this->dcaRequestSwitcher->runWithRequest(
            $this->resolveRequest($request),
            fn () => $this->doGetTrail(...$this->findTableAndId()),
        );
    }

    public function getEditUrl(string $table, int $id): string|null
    {
        $do = $this->findModuleFromTableId($table, $id, null);

        if (!$do) {
            return null;
        }

        $query = [
            'do' => $do,
            'id' => $id,
            'table' => $table,
            'act' => 'edit',
        ];

        return $this->router->generate('contao_backend', $query);
    }

    public function getViewUrl(string $table, int $id): string|null
    {
        $do = $this->findModuleFromTableId($table, $id, null);

        if (!$do) {
            return null;
        }

        $query = $this->dcaRequestSwitcher->runWithRequest(
            new Request(['do' => $do]),
            function () use ($table, $id, $do): array {
                [$ptable, $pid] = $this->findParentFromRecord($table, $id) ?? [null, null];

                $query = [
                    'do' => $do,
                    'table' => $table,
                    'ptable' => $ptable === $table ? $ptable : null,
                    'id' => $pid,
                ];

                (new DcaLoader($table))->load();
                $currentRecord = $this->getCurrentRecord($id, $table);

                // Select the parent node
                if (
                    \in_array(
                        $GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? null,
                        [DataContainer::MODE_TREE_EXTENDED, DataContainer::MODE_TREE],
                        true,
                    )
                ) {
                    $query['pn'] = (int) ($currentRecord['pid'] ?? null);
                }

                return $query;
            },
        );

        return $this->router->generate('contao_backend', $query);
    }

    private function resolveRequest(Request|string|null $request): Request
    {
        if (\is_string($request)) {
            return Request::create($request);
        }

        if ($request instanceof Request) {
            return $request;
        }

        return $this->requestStack->getCurrentRequest() ?? throw new \LogicException('Unable to retrieve DCA information from empty request stack.');
    }

    /**
     * @return list<array{url: string, label: string}>
     */
    private function doGetTrail(string|null $table, int|null $id): array
    {
        $do = $this->findGet('do');

        if (!$table || !$id) {
            if (!$do) {
                return [];
            }

            return [
                [
                    'url' => $this->router->generate('contao_backend', ['do' => $do, 'table' => $table]),
                    'label' => $this->translator->trans("MOD.$do.0", [], 'contao_modules'),
                ],
            ];
        }

        $links = [];
        $trail = $this->findTrail($table, $id);

        foreach (array_reverse($trail, true) as $index => [$table, $row]) {
            $this->framework->getAdapter(System::class)->loadLanguageFile($table);
            (new DcaLoader($table))->load();

            $query = [
                'do' => $do,
                'id' => (int) $row['id'],
            ];

            $childTable = $trail[$index + 1][0] ?? null;

            if ($index === \count($trail) - 1) {
                if (\in_array($this->findGet('table'), $GLOBALS['TL_DCA'][$table]['config']['ctable'] ?? [], true)) {
                    $childTable = $this->findGet('table');
                }

                if ($this->findGet('act')) {
                    $query['act'] = $this->findGet('act');
                }
            }

            if ($childTable) {
                $query['table'] = $childTable;

                if ($childTable === $table) {
                    $query['ptable'] = $table;
                }
            } else {
                $query['table'] = $table;
                $query['act'] ??= 'edit';
            }

            $links[] = [
                'url' => $this->router->generate('contao_backend', $query),
                'label' => $this->recordLabeler->getLabel("contao.db.$table.$row[id]", $row),
            ];
        }

        $links[] = [
            'url' => $this->router->generate('contao_backend', ['do' => $do, 'table' => $table]),
            'label' => $this->translator->trans("MOD.$do.0", [], 'contao_modules'),
        ];

        return array_reverse($links);
    }

    private function findGet(string $key): string|null
    {
        $value = $this->framework->getAdapter(Input::class)->findGet($key);

        return \is_string($value) ? $value : null;
    }

    private function getModule(string $do): array|null
    {
        $this->framework->initialize();

        foreach ($GLOBALS['BE_MOD'] as $group) {
            if (isset($group[$do])) {
                return $group[$do];
            }
        }

        return null;
    }

    private function findTableAndId(): array
    {
        $do = (string) $this->findGet('do');
        $module = $this->getModule($do);

        if (
            !$module
            || (
                true !== ($module['disablePermissionChecks'] ?? null)
                && !$this->securityHelper->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, $do)
            )
        ) {
            return [null, null];
        }

        $tables = (array) ($module['tables'] ?? []);
        $table = (string) $this->findGet('table') ?: $module['tables'][0] ?? null;

        if (!\in_array($table, $tables, true)) {
            return [null, null];
        }

        (new DcaLoader($table))->load();

        if (!is_a(DataContainer::getDriverForTable($table), DC_Table::class, true)) {
            return [null, null];
        }

        if (isset($module['callback']) || isset($module[(string) $this->findGet('key')])) {
            return [$table, null];
        }

        $id = (int) $this->findGet('id') ?: null;
        $pid = (int) $this->findGet('pid') ?: null;
        $act = $this->findGet('act');
        $mode = $this->findGet('mode');

        // For these actions the id parameter refers to the parent record
        if (
            $id
            && (
                ('paste' === $act && 'create' === $mode)
                || \in_array($act, [null, 'select', 'editAll', 'overrideAll', 'deleteAll'], true)
            )
        ) {
            return [$this->findPtable($table, $id), $id];
        }

        // For these actions the pid parameter refers to the insert position
        if (\in_array($act, ['create', 'cut', 'copy', 'cutAll', 'copyAll'], true)) {
            // Mode "paste into"
            if ('2' === $mode) {
                return [$this->findPtable($table, $pid), $pid];
            }

            // Mode "paste after"
            $id = $pid;
        }

        if ('paste' === $act) {
            $currentRecord = $id ? $this->getCurrentRecord($id, $table) : null;

            if ($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? null) {
                $table = (string) ($currentRecord['ptable'] ?? null);
            } else {
                $table = (string) ($GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null);
            }

            $id = (int) ($currentRecord['pid'] ?? null);

            if (!$id || !$table) {
                return [null, null];
            }
        }

        // Current PID would differ from here as it would return the parent
        return [$table, $id];
    }

    private function findPtable(string $table, int|null $id): string|null
    {
        (new DcaLoader($table))->load();

        if (DataContainer::MODE_TREE_EXTENDED === $GLOBALS['TL_DCA'][$table]['list']['sorting']['mode']) {
            return null;
        }

        if ($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? null) {
            $act = $this->findGet('act');
            $mode = $this->findGet('mode');

            // For these actions the id parameter refers to the parent record (or the old
            // record for copy and cut), so they need to be excluded
            if (
                $id
                && ('paste' !== $act || 'create' !== $mode)
                && !\in_array($act, [null, 'copy', 'cut', 'create', 'select', 'copyAll', 'cutAll', 'editAll', 'overrideAll', 'deleteAll'], true)
            ) {
                $currentRecord = $this->getCurrentRecord($id, $table);

                if (!empty($currentRecord['ptable'])) {
                    return $currentRecord['ptable'];
                }
            }

            // Use the ptable query parameter if it points to itself (nested elements case)
            if ($this->findGet('ptable') === $table && \in_array($table, $GLOBALS['TL_DCA'][$table]['config']['ctable'] ?? [], true)) {
                return $table;
            }
        }

        return $GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null;
    }

    /**
     * @return array{string, int}|null
     */
    private function findParentFromRecord(string $table, int $id): array|null
    {
        (new DcaLoader($table))->load();

        if (DataContainer::MODE_TREE_EXTENDED === $GLOBALS['TL_DCA'][$table]['list']['sorting']['mode']) {
            return null;
        }

        $currentRecord = $this->getCurrentRecord($id, $table);
        $pid = (int) ($currentRecord['pid'] ?? null);

        if (!$pid) {
            return null;
        }

        if (!empty($currentRecord['ptable']) && ($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? null)) {
            $ptable = (string) $currentRecord['ptable'];
        } else {
            $ptable = $GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null;
        }

        if (!$ptable) {
            return null;
        }

        return [$ptable, $pid];
    }

    private function findTrail(string $table, int $id): array
    {
        $currentRecord = $this->getCurrentRecord($id, $table);

        if (!$currentRecord) {
            return [];
        }

        $pid = (int) ($currentRecord['pid'] ?? null);

        (new DcaLoader($table))->load();

        if ($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? null) {
            $ptable = (string) ($currentRecord['ptable'] ?? null);
        } else {
            $ptable = (string) ($GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null);
        }

        if (!$ptable || !$pid || DataContainer::MODE_PARENT !== ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? null)) {
            return [[$table, $currentRecord]];
        }

        return [...$this->findTrail($ptable, $pid), [$table, $currentRecord]];
    }

    private function getCurrentRecord(int $id, string $table): array|null
    {
        return (new \ReflectionClass(DC_Table::class))
            ->newInstanceWithoutConstructor()
            ->getCurrentRecord($id, $table)
        ;
    }

    private function findModuleFromTableId(string $table, int $id, array|null $filteredModules): string|null
    {
        $this->framework->initialize();

        $modules = [];

        foreach (null === $filteredModules ? $GLOBALS['BE_MOD'] : [$filteredModules] as $group) {
            foreach ($group as $do => $module) {
                if (\in_array($table, $module['tables'] ?? [], true)) {
                    $modules[$do] = $module;
                }
            }
        }

        if (1 === \count($modules)) {
            return array_keys($modules)[0];
        }

        $record = $this->getCurrentRecord($id, $table);

        if (isset($record['ptable'], $record['pid'])) {
            return $this->findModuleFromTableId($record['ptable'], (int) $record['pid'], $modules);
        }

        return array_keys($modules)[0] ?? null;
    }
}
