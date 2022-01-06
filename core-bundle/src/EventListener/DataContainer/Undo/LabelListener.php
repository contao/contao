<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer\Undo;

use Contao\ArrayUtil;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Date;
use Contao\StringUtil;
use Contao\System;
use Contao\UserModel;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @Callback(target="list.label.label", table="tl_undo")
 *
 * @internal
 */
class LabelListener
{
    use UndoListenerTrait;

    private Environment $twig;

    public function __construct(ContaoFramework $framework, Connection $connection, TranslatorInterface $translator, Environment $twig)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->translator = $translator;
        $this->twig = $twig;
    }

    public function __invoke(array $row, string $label, DataContainer $dc): string
    {
        $this->framework->initialize();

        $table = $row['fromTable'];
        $originalRow = StringUtil::deserialize($row['data'])[$table][0];

        $controller = $this->framework->getAdapter(Controller::class);
        $controller->loadDataContainer($table);

        return $this->twig->render(
            '@ContaoCore/Backend/be_undo_label.html.twig',
            $this->getTemplateData($table, $row, $originalRow)
        );
    }

    private function getTemplateData(string $table, array $row, array $originalRow): array
    {
        $dataContainer = $this->framework->getAdapter(DataContainer::class)->getDriverForTable($table);
        $originalTableDc = new $dataContainer($table);
        $user = $this->framework->getAdapter(UserModel::class)->findById($row['pid']);
        $config = $this->framework->getAdapter(Config::class);
        $parent = null;

        if (true === ($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? null)) {
            $parent = ['table' => $originalRow['ptable'], 'id' => $originalRow['pid']];
        }

        return [
            'preview' => $this->renderPreview($originalRow, $originalTableDc),
            'user' => $user,
            'row' => $row,
            'translatedFromTable' => $this->getTranslatedTypeFromTable($table),
            'parent' => $parent,
            'translatedParentTable' => $parent ? $this->getTranslatedTypeFromTable($parent['table']) : null,
            'originalRow' => $originalRow,
            'dateFormat' => $config->get('dateFormat'),
            'timeFormat' => $config->get('timeFormat'),
        ];
    }

    /**
     * @return array|string
     */
    private function renderPreview(array $arrRow, DataContainer $dc)
    {
        $mode = $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] ?? DataContainer::MODE_SORTED;

        if (DataContainer::MODE_PARENT === $mode) {
            $callback = $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['child_record_callback'] ?? null;

            if (\is_array($callback)) {
                $callable = System::importStatic($callback[0]);

                return $callable->{$callback[1]}($arrRow);
            }

            if (\is_callable($callback)) {
                return $callback($arrRow);
            }
        }

        $labelConfig = &$GLOBALS['TL_DCA'][$dc->table]['list']['label'];
        $labelValues = [];

        foreach ($labelConfig['fields'] as $k => $v) {
            if (false !== strpos($v, ':')) {
                [$strKey, $strTable] = explode(':', $v);
                [$strTable, $strField] = explode('.', $strTable);

                $objRef = $this->connection
                    ->prepare('SELECT '.$strField.' FROM '.$strTable.' WHERE id=? LIMIT 1')
                    ->executeQuery($arrRow[$strKey])
                ;

                $labelValues[$k] = $objRef->rowCount() ? $objRef->{$strField} : '';
                continue;
            }

            if (\in_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['flag'] ?? null, [DataContainer::SORT_DAY_ASC, DataContainer::SORT_DAY_DESC, DataContainer::SORT_MONTH_ASC, DataContainer::SORT_MONTH_DESC, DataContainer::SORT_YEAR_ASC, DataContainer::SORT_YEAR_DESC], true)) {
                if ('date' === $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['rgxp']) {
                    $labelValues[$k] = $arrRow[$v] ? Date::parse(Config::get('dateFormat'), $arrRow[$v]) : '-';
                } elseif ('time' === $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['rgxp']) {
                    $labelValues[$k] = $arrRow[$v] ? Date::parse(Config::get('timeFormat'), $arrRow[$v]) : '-';
                } else {
                    $labelValues[$k] = $arrRow[$v] ? Date::parse(Config::get('datimFormat'), $arrRow[$v]) : '-';
                }
                continue;
            }

            if (($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['isBoolean'] ?? null) || ('checkbox' === $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['inputType'] && !$GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['multiple'])) {
                $labelValues[$k] = $arrRow[$v] ? $this->translator->trans('MSC.yes', [], 'contao_default') : $this->translator->trans('MSC.no', [], 'contao_default');
                continue;
            }

            $row_v = StringUtil::deserialize($arrRow[$v]);

            if (\is_array($row_v)) {
                $args_k = [];

                foreach ($row_v as $option) {
                    $args_k[] = $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$option] ?: $option;
                }

                $labelValues[$k] = implode(', ', $args_k);
                continue;
            }

            if (isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]])) {
                $labelValues[$k] = \is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]]) ? $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]][0] : $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]];
                continue;
            }

            if (($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['isAssociative'] ?? null) || (ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['options'] ?? null) && isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['options'][$arrRow[$v]]))) {
                $labelValues[$k] = $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['options'][$arrRow[$v]];
                continue;
            }

            $labelValues[$k] = $arrRow[$v];
        }

        $label = $labelConfig['format'] ? vsprintf($labelConfig['format'], $labelValues) : implode(', ', $labelValues);

        // Remove empty brackets (), [], {}, <> and empty tags from the label
        $label = preg_replace('/\( *\) ?|\[ *] ?|{ *} ?|< *> ?/', '', $label);
        $label = preg_replace('/<[^>]+>\s*<\/[^>]+>/', '', $label);

        if (\is_array($labelConfig['label_callback'] ?? null)) {
            $callable = System::importStatic($labelConfig['label_callback'][0]);

            if (\in_array($mode, [DataContainer::MODE_TREE, DataContainer::MODE_TREE_EXTENDED], true)) {
                return $callable->{$labelConfig['label_callback'][1]}($arrRow, $label, $dc, '', false, null);
            }

            return $callable->{$labelConfig['label_callback'][1]}($arrRow, $label, $dc, $labelValues);
        }

        if (\is_callable($labelConfig['label_callback'] ?? null)) {
            if (\in_array($mode, [DataContainer::MODE_TREE, DataContainer::MODE_TREE_EXTENDED], true)) {
                return $labelConfig['label_callback']($arrRow, $label, $dc, '', false, null);
            }

            return $labelConfig['label_callback']($arrRow, $label, $dc, $labelValues);
        }

        return $label ?: (string) $arrRow['id'];
    }
}
