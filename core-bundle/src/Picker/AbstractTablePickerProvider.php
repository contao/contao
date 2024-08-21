<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\DcaLoader;
use Doctrine\DBAL\Connection;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractTablePickerProvider implements PickerProviderInterface, DcaPickerProviderInterface, PickerMenuInterface
{
    private const PREFIX = 'dc.';

    private const PREFIX_LENGTH = 3;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly FactoryInterface $menuFactory,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly Connection $connection,
    ) {
    }

    public function getUrl(PickerConfig $config): string
    {
        $table = $this->getTableFromContext($config->getContext());

        if (!$modules = $this->getModulesForTable($table)) {
            throw new \RuntimeException(\sprintf('Table "%s" is not in any back end module (context: %s)', $table, $config->getContext()));
        }

        $module = array_keys($modules)[0];
        [$ptable, $pid, $id] = $this->getPtableAndPid($table, $config->getValue());

        // If we have no selected entry, we need to start at the topmost parent table.
        // This can also result in setting it to null for dynamic parent tables.
        if (!$id) {
            $table = $this->findTopMostParent($table);
        }

        if ($ptable) {
            foreach ($modules as $key => $tables) {
                if (\in_array($ptable, $tables, true)) {
                    $module = $key;
                    break;
                }
            }
        }

        // If the table is the first in the module, we do not need to add table=xy to the URL
        if (0 === array_search($table, $modules[$module], true)) {
            return $this->getUrlForValue($config, $module);
        }

        return $this->getUrlForValue($config, $module, $table, $pid);
    }

    public function addMenuItems(ItemInterface $menu, PickerConfig $config): void
    {
        $modules = array_keys($this->getModulesForTable($this->getTableFromContext($config->getContext())));

        foreach ($modules as $name) {
            $params = [
                'do' => $name,
                'popup' => '1',
                'picker' => $config->cloneForCurrent($this->getName().'.'.$name)->urlEncode(),
            ];

            $menu->addChild($this->menuFactory->createItem(
                $name,
                [
                    'label' => $this->translator->trans('MOD.'.$name.'.0', [], 'contao_default'),
                    'linkAttributes' => ['class' => $name],
                    'current' => $this->isCurrent($config) && $name === substr($config->getCurrent(), \strlen($this->getName().'.')),
                    'uri' => $this->router->generate('contao_backend', $params),
                ],
            ));
        }
    }

    public function createMenuItem(PickerConfig $config): ItemInterface
    {
        $menu = $this->menuFactory->createItem('picker');

        $this->addMenuItems($menu, $config);

        return $menu->getFirstChild();
    }

    public function supportsContext(string $context): bool
    {
        if (!str_starts_with($context, self::PREFIX)) {
            return false;
        }

        $table = $this->getTableFromContext($context);

        $this->framework->initialize();
        $this->framework->createInstance(DcaLoader::class, [$table])->load();

        $dcName = $this->getDataContainer();

        return ($dcName === DataContainer::getDriverForTable($table) || $dcName === $GLOBALS['TL_DCA'][$table]['config']['dataContainer'])
            && [] !== $this->getModulesForTable($table);
    }

    public function supportsValue(PickerConfig $config): bool
    {
        return true;
    }

    public function isCurrent(PickerConfig $config): bool
    {
        return str_starts_with($config->getCurrent(), $this->getName().'.');
    }

    public function getDcaTable(PickerConfig|null $config = null): string
    {
        if (!$config) {
            return '';
        }

        return $this->getTableFromContext($config->getContext());
    }

    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        if ($fieldType = $config->getExtra('fieldType')) {
            $attributes['fieldType'] = $fieldType;
        }

        if ($value = $config->getValue()) {
            $attributes['value'] = array_map(\intval(...), explode(',', $value));
        }

        return $attributes;
    }

    public function convertDcaValue(PickerConfig $config, mixed $value): int|string
    {
        return (int) $value;
    }

    protected function getModulesForTable(string $table): array
    {
        $modules = [];

        foreach ($GLOBALS['BE_MOD'] ?? [] as $v) {
            foreach ($v as $name => $module) {
                if (
                    isset($module['tables'])
                    && \is_array($module['tables'])
                    && \in_array($table, $module['tables'], true)
                ) {
                    $modules[$name] = array_values($module['tables']);
                }
            }
        }

        return $modules;
    }

    protected function getTableFromContext(string $context): string
    {
        return substr($context, self::PREFIX_LENGTH);
    }

    protected function getUrlForValue(PickerConfig $config, string $module, string|null $table = null, int|null $pid = null): string
    {
        $params = [
            'do' => $module,
            'popup' => '1',
            'picker' => $config->cloneForCurrent($this->getName().'.'.$module)->urlEncode(),
        ];

        if (null !== $table) {
            $params['table'] = $table;

            if (null !== $pid) {
                $params['id'] = $pid;
            }
        }

        return $this->router->generate('contao_backend', $params);
    }

    protected function getPtableAndPid(string $table, string $value): array
    {
        // Use the first value if array to find a database record
        $id = (int) explode(',', $value)[0];

        $this->framework->initialize();
        $this->framework->createInstance(DcaLoader::class, [$table])->load();

        $ptable = $GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null;
        $dynamicPtable = $GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? false;
        $data = false;

        if ($id) {
            $qb = $this->connection->createQueryBuilder();
            $qb->select(['id'])->from($table)->where($qb->expr()->eq('id', $id));

            if ($ptable || $dynamicPtable) {
                $qb->addSelect('pid');
            }

            if ($dynamicPtable) {
                $qb->addSelect('ptable');
            }

            $data = $qb->executeQuery()->fetchAssociative();
        }

        return [
            $data['ptable'] ?? $ptable ?? null,
            (int) ($data['pid'] ?? 0) ?: null,
            (int) ($data['id'] ?? 0) ?: null,
        ];
    }

    protected function findTopMostParent(string $table): string|null
    {
        $this->framework->initialize();
        $this->framework->createInstance(DcaLoader::class, [$table])->load();

        if (DataContainer::MODE_PARENT !== ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? null)) {
            return $table;
        }

        $ptable = $GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null;

        if ($ptable && $ptable !== $table) {
            return $this->findTopMostParent($ptable);
        }

        return null;
    }

    /**
     * Returns the DataContainer fully qualified class name (FQCN) supported by this
     * picker (e.g. "Contao\DC_Table" for DC_Table).
     */
    abstract protected function getDataContainer(): string;
}
