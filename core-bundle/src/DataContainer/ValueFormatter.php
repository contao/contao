<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Contao\CoreBundle\DataContainer;

use Contao\ArrayUtil;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Date;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValueFormatter implements ResetInterface
{
    /**
     * @var array<string, array<string, array<scalar, scalar>>>
     */
    private array $foreignValueCache = [];

    /**
     * @var array<string, array<string, array<scalar, scalar|array<scalar, scalar>>>>
     */
    private array $optionsCallbackCache = [];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function reset(): void
    {
        $this->foreignValueCache = [];
        $this->optionsCallbackCache = [];
    }

    public function format(string $table, string $field, mixed $value, mixed $dc): string
    {
        return implode(', ', array_map(
            fn ($v) => $this->getLabel($table, $field, $v, $dc),
            (array) $this->getValues($table, $field, $value),
        ));
    }

    public function formatListing(string $table, string $field, array $row, DataContainer $dc): string|null
    {
        if (str_contains($field, ':')) {
            [$key, $table] = explode(':', $field, 2);
            [$table, $field] = explode('.', $table, 2);

            // Ignore NULL but also 0 value, since 0 is not a valid foreign key ID
            if (!($row[$key] ?? null)) {
                return null;
            }

            return $this->format($table, $field, $this->fetchForeignValue($table, $field, $row[$key]), $dc);
        }

        if (!isset($row[$field])) {
            return null;
        }

        return $this->format($table, $field, $row[$field], $dc);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function formatFilterOptions(string $table, string $field, array $values, DataContainer $dc): array
    {
        $options = [];

        if (
            \in_array(
                (int) ($GLOBALS['TL_DCA'][$table]['fields'][$field]['flag'] ?? null),
                [
                    DataContainer::SORT_DAY_ASC,
                    DataContainer::SORT_DAY_DESC,
                    DataContainer::SORT_DAY_BOTH,
                    DataContainer::SORT_MONTH_ASC,
                    DataContainer::SORT_MONTH_DESC,
                    DataContainer::SORT_MONTH_BOTH,
                    DataContainer::SORT_YEAR_ASC,
                    DataContainer::SORT_YEAR_DESC,
                    DataContainer::SORT_YEAR_BOTH,
                ],
                true,
            )
        ) {
            return $this->getDateOptions($table, $field, $values);
        }

        foreach ($values as $value) {
            $v = $this->getValues($table, $field, $value);

            if (\is_array($v) && !\is_array($value)) {
                foreach ($v as $vv) {
                    $options[] = $vv;
                }
            } else {
                if (\is_array($value)) {
                    $value = implode(', ', $value);
                }

                $options[] = $value;
            }
        }

        $options = array_unique($options);

        foreach ($options as $k => $value) {
            $label = $this->getLabel($table, $field, $value, $dc);

            $options[$label.'_'.$field.'_'.$k] = ['value' => $value, 'label' => $label];
            unset($options[$k]);
        }

        uksort(
            $options,
            static function ($a, $b) {
                $a = (new UnicodeString($a))->folded();
                $b = (new UnicodeString($b))->folded();

                if ($a->toString() === $b->toString()) {
                    return 0;
                }

                return strnatcmp($a->ascii()->toString(), $b->ascii()->toString());
            },
        );

        if (
            \in_array(
                (int) ($GLOBALS['TL_DCA'][$table]['fields'][$field]['flag'] ?? null),
                [
                    DataContainer::SORT_INITIAL_LETTER_DESC,
                    DataContainer::SORT_INITIAL_LETTERS_DESC,
                    DataContainer::SORT_DESC,
                ],
                true,
            )
        ) {
            $options = array_reverse($options, true);
        }

        return array_values($options);
    }

    public function getLabel(string $table, string $field, mixed $value, mixed $dc): string
    {
        // Translate UUIDs to paths
        if ('fileTree' === ($GLOBALS['TL_DCA'][$table]['fields'][$field]['inputType'] ?? null)) {
            $objFile = $this->framework->getAdapter(FilesModel::class)->findByUuid($value);

            if (null !== $objFile) {
                return $objFile->path;
            }
        }

        $dateAdapter = $this->framework->getAdapter(Date::class);
        $configAdapter = $this->framework->getAdapter(Config::class);

        if ('date' === ($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['rgxp'] ?? null)) {
            return $value ? $dateAdapter->parse($configAdapter->get('dateFormat'), $value) : '';
        }

        if ('time' === ($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['rgxp'] ?? null)) {
            return $value ? $dateAdapter->parse($configAdapter->get('timeFormat'), $value) : '';
        }

        if (
            'datim' === ($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['rgxp'] ?? null)
            || 'tstamp' === $field
            || \in_array(
                (int) ($GLOBALS['TL_DCA'][$table]['fields'][$field]['flag'] ?? null),
                [
                    DataContainer::SORT_DAY_ASC,
                    DataContainer::SORT_DAY_DESC,
                    DataContainer::SORT_DAY_BOTH,
                    DataContainer::SORT_MONTH_ASC,
                    DataContainer::SORT_MONTH_DESC,
                    DataContainer::SORT_MONTH_BOTH,
                    DataContainer::SORT_YEAR_ASC,
                    DataContainer::SORT_YEAR_DESC,
                    DataContainer::SORT_YEAR_BOTH,
                ],
                true,
            )
        ) {
            return $value ? $dateAdapter->parse($configAdapter->get('datimFormat'), $value) : '';
        }

        if (isset($GLOBALS['TL_DCA'][$table]['fields'][$field]['reference'][$value])) {
            if (\is_array($GLOBALS['TL_DCA'][$table]['fields'][$field]['reference'][$value])) {
                return (string) ($GLOBALS['TL_DCA'][$table]['fields'][$field]['reference'][$value][0] ?? $value);
            }

            return (string) $GLOBALS['TL_DCA'][$table]['fields'][$field]['reference'][$value];
        }

        if (
            \is_array($GLOBALS['TL_DCA'][$table]['fields'][$field]['options'] ?? null)
            && (
                ($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['isAssociative'] ?? null)
                || ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$table]['fields'][$field]['options'] ?? null)
            )
        ) {
            $label = $this->findOptionLabel($GLOBALS['TL_DCA'][$table]['fields'][$field]['options'], $value);

            if (null !== $label) {
                return $label;
            }
        }

        if ($callbackOptions = $this->fetchOptionsCallback($table, $field, $dc)) {
            $label = $this->findOptionLabel($callbackOptions, $value);

            if (null !== $label) {
                return $label;
            }
        }

        if (
            'pid' === $field
            && !empty($GLOBALS['TL_DCA'][$table]['config']['ptable'])
            && !isset($GLOBALS['TL_DCA'][$table]['fields'][$field]['foreignKey'])
        ) {
            $ptable = $GLOBALS['TL_DCA'][$table]['config']['ptable'];
            $this->framework->getAdapter(Controller::class)->loadDataContainer($ptable);
            $showField = $GLOBALS['TL_DCA'][$ptable]['list']['label']['fields'][0] ?? 'id';

            $GLOBALS['TL_DCA'][$table]['fields'][$field]['foreignKey'] = $ptable.'.'.$showField;
        }

        if (isset($GLOBALS['TL_DCA'][$table]['fields'][$field]['foreignKey'])) {
            if ('' === (string) $value) {
                return '';
            }

            [$table, $field] = explode('.', $GLOBALS['TL_DCA'][$table]['fields'][$field]['foreignKey'], 2);

            return $this->getLabel($table, $field, $this->fetchForeignValue($table, $field, $value), $dc);
        }

        if (
            ($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['isBoolean'] ?? null)
            || (
                'checkbox' === ($GLOBALS['TL_DCA'][$table]['fields'][$field]['inputType'] ?? null)
                && !($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['multiple'] ?? null)
            )
        ) {
            return $this->translator->trans($value ? 'MSC.yes' : 'MSC.no', [], 'contao_default');
        }

        return (string) $value;
    }

    private function getDateOptions(string $table, string $field, array $values): array
    {
        $options = [];

        // Sort by day
        if (
            \in_array(
                (int) ($GLOBALS['TL_DCA'][$table]['fields'][$field]['flag'] ?? null),
                [
                    DataContainer::SORT_DAY_ASC,
                    DataContainer::SORT_DAY_DESC,
                    DataContainer::SORT_DAY_BOTH,
                ],
                true,
            )
        ) {
            DataContainer::SORT_DAY_DESC === ($GLOBALS['TL_DCA'][$table]['fields'][$field]['flag'] ?? null) ? rsort($values) : sort($values);
            $dateAdapter = $this->framework->getAdapter(Date::class);
            $configAdapter = $this->framework->getAdapter(Config::class);

            foreach ($values as $v) {
                $options[] = ['value' => $v, 'label' => $v ? $dateAdapter->parse($configAdapter->get('dateFormat'), $v) : '-'];
            }

            return $options;
        }

        // Sort by month
        if (
            \in_array(
                (int) ($GLOBALS['TL_DCA'][$table]['fields'][$field]['flag'] ?? null),
                [
                    DataContainer::SORT_MONTH_ASC,
                    DataContainer::SORT_MONTH_DESC,
                    DataContainer::SORT_MONTH_BOTH,
                ],
                true,
            )
        ) {
            DataContainer::SORT_MONTH_DESC === ($GLOBALS['TL_DCA'][$table]['fields'][$field]['flag'] ?? null) ? rsort($values) : sort($values);

            foreach ($values as $v) {
                if ('' === $v) {
                    $options[] = ['value' => $v, 'label' => '-'];
                    continue;
                }

                $id = 'MONTHS.'.(date('m', (int) $v) - 1);
                $month = $this->translator->trans($id, [], 'contao_default');

                if ($month !== $id) {
                    $options[] = ['value' => $v, 'label' => $month.' '.date('Y', (int) $v)];
                } else {
                    $options[] = ['value' => $v, 'label' => date('Y-m', (int) $v)];
                }
            }

            return $options;
        }

        // Sort by year
        if (
            \in_array(
                (int) ($GLOBALS['TL_DCA'][$table]['fields'][$field]['flag'] ?? null),
                [
                    DataContainer::SORT_YEAR_ASC,
                    DataContainer::SORT_YEAR_DESC,
                    DataContainer::SORT_YEAR_BOTH,
                ],
                true,
            )
        ) {
            DataContainer::SORT_YEAR_DESC === ($GLOBALS['TL_DCA'][$table]['fields'][$field]['flag'] ?? null) ? rsort($values) : sort($values);

            foreach ($values as $v) {
                $options[] = ['value' => $v, 'label' => $v ? date('Y', (int) $v) : '-'];
            }

            return $options;
        }

        throw new \InvalidArgumentException(\sprintf('Field "%s" of table "%s" is not sortable by date.', $field, $table));
    }

    /**
     * Unpacks serialized or CSV-separated values.
     */
    private function getValues(string $table, string $field, mixed $value): mixed
    {
        if (isset($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['csv'])) {
            return StringUtil::trimsplit($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['csv'], $value);
        }

        if ($GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['multiple'] ?? null) {
            return StringUtil::deserialize($value, true);
        }

        return $value;
    }

    private function fetchForeignValue(string $table, string $field, mixed $id): mixed
    {
        // Cannot use isset() because the value can be NULL
        if (!\array_key_exists($id, $this->foreignValueCache[$table][$field] ?? [])) {
            $dbField = $field;
            if (preg_match('/^[A-Za-z0-9_$]+$/', $field)) {
                $dbField = $this->connection->getDatabasePlatform()->quoteSingleIdentifier($field);
            }

            $value = $this->connection->fetchOne("SELECT $dbField FROM $table WHERE id=?", [$id]);

            $this->foreignValueCache[$table][$field][$id] = false === $value ? $id : $value;
        }

        return $this->foreignValueCache[$table][$field][$id];
    }

    private function fetchOptionsCallback(string $table, string $field, mixed $dc): array|null
    {
        // Cannot use isset() because the value can be NULL
        if (\array_key_exists($field, $this->optionsCallbackCache[$table] ?? [])) {
            return $this->optionsCallbackCache[$table][$field];
        }

        if (\is_array($GLOBALS['TL_DCA'][$table]['fields'][$field]['options_callback'] ?? null)) {
            [$class, $method] = $GLOBALS['TL_DCA'][$table]['fields'][$field]['options_callback'];

            return $this->optionsCallbackCache[$table][$field] = $this->framework->getAdapter(System::class)->importStatic($class)->$method($dc);
        }

        if (\is_callable($GLOBALS['TL_DCA'][$table]['fields'][$field]['options_callback'] ?? null)) {
            return $this->optionsCallbackCache[$table][$field] = $GLOBALS['TL_DCA'][$table]['fields'][$field]['options_callback']($dc);
        }

        return $this->optionsCallbackCache[$table][$field] = null;
    }

    private function findOptionLabel(array $options, mixed $value): string|null
    {
        foreach ($options as $k => $v) {
            if ((string) $k === (string) $value) {
                return (string) $v;
            }

            if (\is_array($v)) {
                foreach ($v as $kk => $vv) {
                    if ((string) $kk === (string) $value) {
                        return $vv;
                    }
                }
            }
        }

        return null;
    }
}
