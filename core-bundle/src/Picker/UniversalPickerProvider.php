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
use Doctrine\DBAL\Connection;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UniversalPickerProvider implements PickerProviderInterface, DcaPickerProviderInterface, PickerMenuInterface
{
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

    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, TranslatorInterface $translator, Connection $connection)
    {
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
        $table = $this->getTable($config->getContext());
        $modules = $this->getModulesForTable($config->getContext());
        $value = $config->getValue();

        if (!$value) {
            return $this->getUrlForValue($config, array_keys($modules)[0]);
        }

        // Use the first value if array to find a database record
        $value = array_map('\intval', explode(',', $value))[0];

        Controller::loadDataContainer($table);

        $pid = null;
        $ptable = $GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null;
        $dynamicPtable = $GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? false;

        if ($ptable || $dynamicPtable) {
            $qb = $this->connection->createQueryBuilder();
            $qb->select('pid')->from($table)->where($qb->expr()->eq('id', $value));

            if ($dynamicPtable) {
                $qb->addSelect('ptable');
            }

            $data = $qb->execute()->fetch();

            if (false === $data) {
                return $this->getUrlForValue($config, array_keys($modules)[0]);
            }

            $pid = $data['pid'];

            if ($dynamicPtable) {
                $ptable = $data['ptable'] ?: $ptable;

                // Backwards compatibility, assuming old data in tl_content
                if (!$ptable) {
                    $ptable = 'tl_article';
                }
            }
        }

        if (1 === \count($modules) || !$ptable) {
            return $this->getUrlForValue($config, array_keys($modules)[0]);
        }

        foreach ($modules as $module => $tables) {
            if (\in_array($ptable, $tables, true)) {
                return $this->getUrlForValue($config, $module, $table, $pid);
            }
        }

        return $this->getUrlForValue($config, array_keys($modules)[0], $table, $pid);
    }

    public function addMenuItems(ItemInterface $menu, PickerConfig $config): void
    {
        $modules = array_keys($this->getModulesForTable($config->getContext()));

        foreach ($modules as $name) {
            $params = array_merge(
                [
                    'do' => $name,
                    'popup' => '1',
                    'picker' => $config->cloneForCurrent('universal.'.$name)->urlEncode(),
                ]
            );

            $menu->addChild($this->menuFactory->createItem(
                $name,
                [
                    'label' => $this->translator->trans('MOD.'.$name.'.0', [], 'contao_default'),
                    'linkAttributes' => ['class' => $name],
                    'current' => $this->isCurrent($config) && substr($config->getCurrent(), 10) === $name,
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
        return 0 === strpos($context, 'universal.') && 0 !== \count($this->getModulesForTable($context));
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
        return 0 === strpos($config->getCurrent(), 'universal.');
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

        return $this->getTable($config->getContext());
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

    private function getModulesForTable(string $context): array
    {
        $table = $this->getTable($context);
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

    private function getTable(string $context): string
    {
        return substr($context, 10);
    }

    private function getUrlForValue(PickerConfig $config, string $module, string $table = null, string $pid = null): string
    {
        $params = [
            'do' => $module,
            'popup' => '1',
            'picker' => $config->cloneForCurrent('universal.'.$module)->urlEncode(),
        ];

        if (null !== $table) {
            $params['table'] = $table;

            if (null !== $pid) {
                $params['id'] = $pid;
            }
        }

        return $this->router->generate('contao_backend', $params);
    }
}
