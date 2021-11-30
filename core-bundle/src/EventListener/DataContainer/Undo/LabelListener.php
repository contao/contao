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
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception;

/**
 * @Callback(target="list.label.label", table="tl_undo")
 *
 * @internal
 */
class LabelListener
{
    use UndoListenerTrait;

    private Connection $connection;
    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework, Connection $connection)
    {
        $this->framework = $framework;
        $this->connection = $connection;
    }

    public function __invoke(array $row, string $label, DataContainer $dc): string
    {
        $this->framework->initialize();

        $table = $row['fromTable'];
        $originalRow = StringUtil::deserialize($row['data'])[$table][0];

        /** @var Controller $controller */
        $controller = $this->framework->getAdapter(Controller::class);
        $controller->loadDataContainer($table);
        $controller->loadLanguageFile($table);

        $dataContainer = $this->framework->getAdapter(DataContainer::class)->getDriverForTable($table);
        $fromTableDc = new $dataContainer($table);

        $header = $this->renderHeader($table, $row, $originalRow);
        $newLabel = $this->renderLabel($originalRow, $fromTableDc);

        if ($GLOBALS['TL_DCA'][$table]['list']['label']['showColumns'] ?? false) {
            $newLabel = $this->renderColumns($newLabel);
        }

        return $header.'<div class="tl_undo_preview">'.$newLabel.'</div>';
    }

    private function renderHeader(string $table, array $row, array $originalRow): string
    {
        /** @var UserModel $userModel */
        $userModel = $this->framework->getAdapter(UserModel::class);
        $user = $userModel->findById($row['pid']);
        $parent = $this->getParentTableForRow($table, $originalRow);
        $type = $this->getTranslatedTypeFromTable($table);

        $header = '<div class="tl_undo_header">';

        $header .= sprintf(
            '<div class="tl_undo_header_item"><span class="tl_undo_header_label"><span class="date">%s</span> <span class="time">%s</span></span></div>',
            Date::parse(Config::get('dateFormat'), $row['tstamp']),
            Date::parse(Config::get('timeFormat'), $row['tstamp']),
        );

        $header .= sprintf(
            '<div class="tl_undo_header_item"><span class="tl_undo_header_label">%s</span> <strong>%s</strong></div>',
            $GLOBALS['TL_LANG']['tl_undo']['pid'][0],
            $user ? $user->username : $row['pid'],
        );

        $header .= sprintf(
            '<div class="tl_undo_header_item"><span class="tl_undo_header_label">%s</span> <strong>%s</strong></div>',
            $GLOBALS['TL_LANG']['tl_undo']['fromTable'][0],
            $type,
        );

        $header .= sprintf(
            '<div class="tl_undo_header_item"><span class="tl_undo_header_label">ID</span> %s</div>',
            $originalRow['id'],
        );

        if ($parent) {
            $header .= sprintf(
                '<div class="tl_undo_header_item"><span class="tl_undo_header_label">%s</span> <strong>%s</strong></div>',
                $GLOBALS['TL_LANG']['tl_undo']['parent'],
                $this->getTranslatedTypeFromTable($parent['table'])
            );
        }

        $header .= '</div>';

        return $header;
    }

    /**
     * @throws Exception
     * @throws DriverException
     *
     * @return array|string
     */
    private function renderLabel(array $arrRow, DataContainer $dc)
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
            } elseif (\in_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['flag'], [DataContainer::SORT_DAY_ASC, DataContainer::SORT_DAY_DESC, DataContainer::SORT_MONTH_ASC, DataContainer::SORT_MONTH_DESC, DataContainer::SORT_YEAR_ASC, DataContainer::SORT_YEAR_DESC], true)) {
                if ('date' === $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['rgxp']) {
                    $labelValues[$k] = $arrRow[$v] ? Date::parse(Config::get('dateFormat'), $arrRow[$v]) : '-';
                } elseif ('time' === $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['rgxp']) {
                    $labelValues[$k] = $arrRow[$v] ? Date::parse(Config::get('timeFormat'), $arrRow[$v]) : '-';
                } else {
                    $labelValues[$k] = $arrRow[$v] ? Date::parse(Config::get('datimFormat'), $arrRow[$v]) : '-';
                }
            } elseif ($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['isBoolean'] || ('checkbox' === $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['inputType'] && !$GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['multiple'])) {
                $labelValues[$k] = $arrRow[$v] ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
            } else {
                $row_v = StringUtil::deserialize($arrRow[$v]);

                if (\is_array($row_v)) {
                    $args_k = [];

                    foreach ($row_v as $option) {
                        $args_k[] = $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$option] ?: $option;
                    }

                    $labelValues[$k] = implode(', ', $args_k);
                } elseif (isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]])) {
                    $labelValues[$k] = \is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]]) ? $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]][0] : $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]];
                } elseif (($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['isAssociative'] || ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['options'])) && isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['options'][$arrRow[$v]])) {
                    $labelValues[$k] = $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['options'][$arrRow[$v]];
                } else {
                    $labelValues[$k] = $arrRow[$v];
                }
            }
        }

        if ($labelConfig['format']) {
            $label = vsprintf($labelConfig['format'], $labelValues);
            // Remove empty brackets (), [], {}, <> and empty tags from the label
            $label = preg_replace('/\( *\) ?|\[ *] ?|{ *} ?|< *> ?/', '', $label);
            $label = preg_replace('/<[^>]+>\s*<\/[^>]+>/', '', $label);
        } else {
            $label = implode(', ', $labelValues);
        }

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

    private function renderColumns(array $label): string
    {
        $html = '<table style="width: 100%;"><tr>';

        foreach ($label as $field) {
            $html .= '<td>'.$field.'</td>';
        }

        $html .= '</tr></table>';

        return $html;
    }
}
