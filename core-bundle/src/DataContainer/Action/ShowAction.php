<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer\Action;

use Contao\ArrayUtil;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\FilesModel;
use Contao\Idna;
use Contao\StringUtil;
use Contao\System;

/**
 * @internal
 */
class ShowAction implements ActionInterface
{
    public function render(DataContainer $dc): string
    {
        $currentRecord = $dc->getCurrentRecord();

        if (null === $currentRecord) {
            return '';
        }

        $data = [];
        $row = $currentRecord;

        // Get all fields
        $fields = array_keys($row);
        $allowedFields = ['id', 'pid', 'sorting', 'tstamp'];

        if (\is_array($GLOBALS['TL_DCA'][$dc->table]['fields'] ?? null)) {
            $allowedFields = array_unique([...$allowedFields, ...array_keys($GLOBALS['TL_DCA'][$dc->table]['fields'])]);
        }

        // Use the field order of the DCA file
        $fields = array_intersect($allowedFields, $fields);
        $db = Database::getInstance();

        // Show all allowed fields
        foreach ($fields as $i) {
            if (($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['inputType'] ?? null) === 'password' || ($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['eval']['doNotShow'] ?? null) || ($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['eval']['hideInput'] ?? null) || !\in_array($i, $allowedFields, true)) {
                continue;
            }

            $value = StringUtil::deserialize($row[$i]);

            // Get the field value
            if (isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['foreignKey'])) {
                $temp = [];
                $chunks = explode('.', $GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['foreignKey'], 2);

                foreach ((array) $value as $v) {
                    $objKey = $db
                        ->prepare('SELECT '.Database::quoteIdentifier($chunks[1]).' AS value FROM '.$chunks[0].' WHERE id=?')
                        ->limit(1)
                        ->execute($v)
                    ;

                    if ($objKey->numRows) {
                        $temp[] = $objKey->value;
                    }
                }

                $row[$i] = implode(', ', $temp);
            } elseif (($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['inputType'] ?? null) === 'fileTree') {
                if (\is_array($value)) {
                    foreach ($value as $kk => $vv) {
                        if ($objFile = FilesModel::findByUuid($vv)) {
                            $value[$kk] = $objFile->path.' ('.StringUtil::binToUuid($vv).')';
                        } else {
                            $value[$kk] = '';
                        }
                    }

                    $row[$i] = implode(', ', $value);
                } elseif ($objFile = FilesModel::findByUuid($value)) {
                    $row[$i] = $objFile->path.' ('.StringUtil::binToUuid($value).')';
                } else {
                    $row[$i] = '';
                }
            } elseif (\is_array($value)) {
                if (isset($value['value'], $value['unit']) && 2 === \count($value)) {
                    $row[$i] = trim($value['value'].', '.$value['unit']);
                } else {
                    foreach ($value as $kk => $vv) {
                        if (\is_array($vv)) {
                            $vals = array_values($vv);
                            $value[$kk] = array_shift($vals).' ('.implode(', ', array_filter($vals)).')';
                        }
                    }

                    if (ArrayUtil::isAssoc($value)) {
                        foreach ($value as $kk => $vv) {
                            $value[$kk] = $kk.': '.$vv;
                        }
                    }

                    $row[$i] = implode(', ', $value);
                }
            } elseif (($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['eval']['rgxp'] ?? null) === 'date') {
                $row[$i] = $value ? Date::parse(Config::get('dateFormat'), $value) : '-';
            } elseif (($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['eval']['rgxp'] ?? null) === 'time') {
                $row[$i] = $value ? Date::parse(Config::get('timeFormat'), $value) : '-';
            } elseif ('tstamp' === $i || ($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['eval']['rgxp'] ?? null) === 'datim' || \in_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['flag'] ?? null, [DataContainer::SORT_DAY_ASC, DataContainer::SORT_DAY_DESC, DataContainer::SORT_DAY_BOTH, DataContainer::SORT_MONTH_ASC, DataContainer::SORT_MONTH_DESC, DataContainer::SORT_MONTH_BOTH, DataContainer::SORT_YEAR_ASC, DataContainer::SORT_YEAR_DESC, DataContainer::SORT_YEAR_BOTH], true)) {
                $row[$i] = $value ? Date::parse(Config::get('datimFormat'), $value) : '-';
            } elseif (($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['eval']['isBoolean'] ?? null) || (($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['inputType'] ?? null) === 'checkbox' && !($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['eval']['multiple'] ?? null))) {
                $row[$i] = $value ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
            } elseif (($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['eval']['rgxp'] ?? null) === 'email') {
                $row[$i] = Idna::decodeEmail($value);
            } elseif (($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['inputType'] ?? null) === 'textarea' && (($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['eval']['allowHtml'] ?? null) || ($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['eval']['preserveTags'] ?? null))) {
                $row[$i] = StringUtil::specialchars($value);
            } elseif (\is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['reference'] ?? null)) {
                $row[$i] = isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['reference'][$row[$i]]) ? (\is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['reference'][$row[$i]]) ? $GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['reference'][$row[$i]][0] : $GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['reference'][$row[$i]]) : $row[$i];
            } elseif (($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['eval']['isAssociative'] ?? null) || ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['options'] ?? null)) {
                $row[$i] = $GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['options'][$row[$i]] ?? null;
            } else {
                $row[$i] = $value;
            }

            $label = null;

            // Label
            if (isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['label'])) {
                $label = \is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['label']) ? $GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['label'][0] : $GLOBALS['TL_DCA'][$dc->table]['fields'][$i]['label'];
            } elseif (isset($GLOBALS['TL_LANG']['MSC'][$i])) {
                $label = \is_array($GLOBALS['TL_LANG']['MSC'][$i]) ? $GLOBALS['TL_LANG']['MSC'][$i][0] : $GLOBALS['TL_LANG']['MSC'][$i];
            }

            if (!$label) {
                $label = '-';
            }

            $label .= ' <small>'.$i.'</small>';

            $data[$dc->table][0][$label] = $row[$i];
        }

        // Call onshow_callback
        if (\is_array($GLOBALS['TL_DCA'][$dc->table]['config']['onshow_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA'][$dc->table]['config']['onshow_callback'] as $callback) {
                if (\is_array($callback)) {
                    $data = System::importStatic($callback[0])->{$callback[1]}($data, $currentRecord, $dc);
                } elseif (\is_callable($callback)) {
                    $data = $callback($data, $currentRecord, $dc);
                }
            }
        }

        $separate = false;
        $return = '';

        // Generate table
        foreach ($data as $table => $rows) {
            foreach ($rows as $entries) {
                if ($separate) {
                    $return .= '</tbody></table>';
                }

                $separate = true;

                $return .= '
<table class="tl_show with-padding with-zebra">
  <thead>
    <tr>
      <th class="tl_label">'.$GLOBALS['TL_LANG']['MSC']['table'].'</th>
      <th>'.$table.'</th>
    </tr>
  </thead>
  <tbody>';

                foreach ($entries as $lbl => $val) {
                    // Always encode special characters (thanks to Oliver Klee)
                    $return .= '
	  <tr>
		<td class="tl_label">'.$lbl.'</td>
		<td>'.StringUtil::specialchars($val).'</td>
	  </tr>';
                }
            }
        }

        return $return.'</tbody></table>';
    }
}
