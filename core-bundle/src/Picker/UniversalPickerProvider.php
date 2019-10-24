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

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DcaLoader;
use Doctrine\DBAL\Connection;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UniversalPickerProvider implements PickerProviderInterface, DcaPickerProviderInterface, PickerMenuInterface
{
    private const PREFIX = 'universal.';
    private const PREFIX_LENGTH = 10;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var FactoryInterface
     */
    private $menuFactory;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(ContaoFramework $framework, FactoryInterface $menuFactory, RouterInterface $router, TranslatorInterface $translator, Connection $connection)
    {
        $this->framework = $framework;
        $this->menuFactory = $menuFactory;
        $this->router = $router;
        $this->translator = $translator;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'universalPicker';
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(PickerConfig $config): string
    {
        $table = $this->getTableFromContext($config->getContext());
        $modules = $this->getModulesForTable($table);

        if (0 === \count($modules)) {
            throw new \RuntimeException(sprintf('Table %s is not in any back end module (context: %s)', $table, $config->getContext()));
        }

        $module = array_keys($modules)[0];
        [$ptable, $pid] = $this->getPtableAndPid($table, $config->getValue());

        if ($ptable) {
            foreach ($modules as $k => $tables) {
                if (\in_array($ptable, $tables, true)) {
                    $module = $k;
                    break;
                }
            }
        }

        if (0 === array_search($table, $modules[$module], true)) {
            return $this->getUrlForValue($config, $module);
        }

        return $this->getUrlForValue($config, $module, $table, $pid);
    }

    public function addMenuItems(ItemInterface $menu, PickerConfig $config): void
    {
        $modules = array_keys($this->getModulesForTable($this->getTableFromContext($config->getContext())));

        foreach ($modules as $name) {
            $params = array_merge(
                [
                    'do' => $name,
                    'popup' => '1',
                    'picker' => $config->cloneForCurrent(self::PREFIX.$name)->urlEncode(),
                ]
            );

            $menu->addChild($this->menuFactory->createItem(
                $name,
                [
                    'label' => $this->translator->trans('MOD.'.$name.'.0', [], 'contao_default'),
                    'linkAttributes' => ['class' => $name],
                    'current' => $this->isCurrent($config) && substr($config->getCurrent(), self::PREFIX_LENGTH) === $name,
                    'uri' => $this->router->generate('contao_backend', $params),
                ]
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createMenuItem(PickerConfig $config): ItemInterface
    {
        $menu = $this->menuFactory->createItem('picker');

        $this->addMenuItems($menu, $config);

        return $menu->getFirstChild();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context): bool
    {
        return 0 === strpos($context, self::PREFIX) && 0 !== \count($this->getModulesForTable($this->getTableFromContext($context)));
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent(PickerConfig $config): bool
    {
        return 0 === strpos($config->getCurrent(), self::PREFIX);
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable(/* PickerConfig $config */): string
    {
        $config = func_get_arg(0);

        if (!$config instanceof PickerConfig) {
            return '';
        }

        return $this->getTableFromContext($config->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        $value = $config->getValue();

        if ($fieldType = $config->getExtra('fieldType')) {
            $attributes['fieldType'] = $fieldType;
        }

        if ($source = $config->getExtra('source')) {
            $attributes['preserveRecord'] = $source;
        }

        if ($value) {
            $attributes['value'] = array_map('\intval', explode(',', $value));
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value)
    {
        return (int) $value;
    }

    private function getModulesForTable(string $table): array
    {
        $modules = [];

        foreach ($GLOBALS['BE_MOD'] as $v) {
            foreach ($v as $name => $module) {
                if (isset($module['tables']) && \is_array($module['tables']) && \in_array($table, $module['tables'], true)) {
                    $modules[$name] = array_values($module['tables']);
                }
            }
        }

        return $modules;
    }

    private function getTableFromContext(string $context): string
    {
        return substr($context, self::PREFIX_LENGTH);
    }

    private function getUrlForValue(PickerConfig $config, string $module, string $table = null, string $pid = null): string
    {
        $params = [
            'do' => $module,
            'popup' => '1',
            'picker' => $config->cloneForCurrent(self::PREFIX.$module)->urlEncode(),
        ];

        if (null !== $table) {
            $params['table'] = $table;

            if (null !== $pid) {
                $params['id'] = $pid;
            }
        }

        return $this->router->generate('contao_backend', $params);
    }

    private function getPtableAndPid(string $table, string $value): array
    {
        // Use the first value if array to find a database record
        $id = (int) explode(',', $value)[0];

        if (!$value) {
            return [null, null];
        }

        $this->framework->initialize();
        $this->framework->createInstance(DcaLoader::class, [$table])->load();

        $pid = null;
        $ptable = $GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null;
        $dynamicPtable = $GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? false;

        if (!$ptable && !$dynamicPtable) {
            return [null, null];
        }

        $qb = $this->connection->createQueryBuilder();
        $qb->select('pid')->from($table)->where($qb->expr()->eq('id', $id));

        if ($dynamicPtable) {
            $qb->addSelect('ptable');
        }

        $data = $qb->execute()->fetch();

        if (false === $data) {
            return [null, null];
        }

        $pid = $data['pid'];

        if ($dynamicPtable) {
            $ptable = $data['ptable'] ?: $ptable;

            // Backwards compatibility, assuming old data in tl_content
            if (!$ptable) {
                $ptable = 'tl_article';
            }
        }

        return [$ptable, $pid];
    }
}
