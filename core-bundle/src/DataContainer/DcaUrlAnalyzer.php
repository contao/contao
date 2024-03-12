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

class DcaUrlAnalyzer
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
        private readonly Security $securityHelper,
        private readonly RouterInterface $router,
    ) {
    }

    public function getCurrentTableId(Request|null $request = null): array
    {
        $request ??= $this->requestStack->getCurrentRequest();

        if (!$request) {
            return [null, null];
        }

        return $this->findTableAndId($request);
    }

    public function getTrail(Request|null $request = null): array
    {
        [$table, $id] = $this->getCurrentTableId($request);

        if (!$table || !$id) {
            return [];
        }

        $links = [];

        $do = Input::findGet('do', $request);
        $trail = $this->findTrail($table, $id);

        for ($i = \count($trail) - 1; $i >= 0; --$i) {
            [$table, $row] = $trail[$i];
            $childTable = $trail[$i + 1][0] ?? null;

            $query = ['do' => $do];
            $query['id'] = (int) $row['id'];

            if ($childTable) {
                $query['table'] = $childTable;
                if ($childTable === $table) {
                    $query['ptable'] = $table;
                }
            } else {
                $query['table'] = $table;
                $query['act'] = 'edit';
            }

            System::loadLanguageFile($table);
            (new DcaLoader($table))->load();

            $links[] = [
                'url' => $this->router->generate('contao_backend', $query),
                'label' => $this->renderLabel($row, $table),
            ];
        }

        $links[] = [
            'url' => $this->router->generate('contao_backend', ['do' => $do, 'table' => $table]),
            'label' => '??? root',
        ];

        return array_reverse($links);
    }

    private function renderLabel(array $row, string $table): string
    {
        System::loadLanguageFile($table);
        (new DcaLoader($table))->load();
        $dc = (new \ReflectionClass(DC_Table::class))->newInstanceWithoutConstructor();

        $mode = $GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? DataContainer::MODE_SORTED;

        if (DataContainer::MODE_PARENT === $mode && ($GLOBALS['TL_DCA'][$table]['list']['sorting']['child_record_callback'] ?? null)) {
            return '??? '.$table.'.'.$row['id'];
        }

        $label = trim(strip_tags($dc->generateRecordLabel($row, $table)));

        return $label ?: $row['id'] ?? '';
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

    private function findTableAndId(Request $request): array
    {
        $do = (string) Input::findGet('do', $request);
        $module = $this->getModule($do);

        if (!$module || (($module['disablePermissionChecks'] ?? null) !== true && !$this->securityHelper->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, $do))) {
            return [null, null];
        }

        $tables = (array) ($module['tables'] ?? []);
        $table = (string) Input::findGet('table', $request) ?: $module['tables'][0] ?? null;

        if (!\in_array($table, $tables, true)) {
            return [null, null];
        }

        (new DcaLoader($table))->load();

        if (!is_a(DataContainer::getDriverForTable($table), DC_Table::class, true)) {
            return [null, null];
        }

        if (isset($module['callback']) || isset($module[(string) Input::findGet('key', $request)])) {
            return [$table, null];
        }

        $id = (int) Input::findGet('id', $request) ?: null;
        $pid = (int) Input::findGet('pid', $request) ?: null;
        $act = Input::findGet('act', $request);
        $mode = Input::findGet('mode', $request);

        // For these actions the id parameter refers to the parent record
        if (('paste' === $act && 'create' === $mode) || \in_array($act, [null, 'select', 'editAll', 'overrideAll', 'deleteAll'], true)) {
            return [$this->findPtable($table, $id, $request), $id];
        }

        // For these actions the pid parameter refers to the insert position
        if (\in_array($act, ['create', 'cut', 'copy', 'cutAll', 'copyAll'], true)) {
            // Mode “paste into”
            if ('2' === $mode) {
                return [$this->findPtable($table, $pid, $request), $pid];
            }

            // Mode “paste after”
            $id = $pid;
        }

        if ('paste' === $act) {
            $dc = (new \ReflectionClass(DC_Table::class))->newInstanceWithoutConstructor();
            $currentRecord = $dc->getCurrentRecord($id, $table);

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

    private function findPtable(string $table, int|null $id, Request $request): string|null
    {
        (new DcaLoader($table))->load();

        if ($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? null) {
            $act = Input::findGet('act', $request);
            $mode = Input::findGet('mode', $request);

            // For these actions the id parameter refers to the parent record (or the old
            // record for copy and cut), so they need to be excluded
            if ($id && ('paste' !== $act || 'create' !== $mode) && !\in_array($act, [null, 'copy', 'cut', 'create', 'select', 'copyAll', 'cutAll', 'editAll', 'overrideAll', 'deleteAll'], true)) {
                $dc = (new \ReflectionClass(DC_Table::class))->newInstanceWithoutConstructor();
                $currentRecord = $dc->getCurrentRecord($id, $table);

                if (!empty($currentRecord['ptable'])) {
                    return $currentRecord['ptable'];
                }
            }

            // Use the ptable query parameter if it points to itself (nested elements case)
            if (Input::findGet('ptable', $request) === $table && \in_array($table, $GLOBALS['TL_DCA'][$table]['config']['ctable'] ?? [], true)) {
                return $table;
            }
        }

        return $GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null;
    }

    private function findTrail(string $table, int $id): array
    {
        $dc = (new \ReflectionClass(DC_Table::class))->newInstanceWithoutConstructor();
        $currentRecord = $dc->getCurrentRecord($id, $table);

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

        if (!$ptable || !$pid || ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? null) !== DataContainer::MODE_PARENT) {
            return [[$table, $currentRecord]];
        }

        return [...$this->findTrail($ptable, $pid), [$table, $currentRecord]];
    }
}
