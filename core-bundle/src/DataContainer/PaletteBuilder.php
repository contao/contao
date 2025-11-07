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

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

/**
 * @internal
 */
class PaletteBuilder
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly Connection $connection,
    ) {
    }

    /**
     * Return the name of the current palette.
     */
    public function getPalette(string $table, int $id, DataContainer $dc): string
    {
        $palette = $GLOBALS['TL_DCA'][$table]['palettes']['default'] ?? '';

        // Check whether there are selector fields
        if (!empty($GLOBALS['TL_DCA'][$table]['palettes']['__selector__'])) {
            $sValues = [];
            $subpalettes = [];

            try {
                $currentRow = $dc->getCurrentRecord($id, $table);
            } catch (AccessDeniedException) {
                $currentRow = null;
            }

            // Get selector values from DB
            if (null !== $currentRow) {
                $inputAdapter = $this->framework->getAdapter(Input::class);

                foreach ($GLOBALS['TL_DCA'][$table]['palettes']['__selector__'] as $name) {
                    $trigger = $currentRow[$name] ?? null;

                    // Overwrite the trigger
                    if ($inputAdapter->post('FORM_SUBMIT') === $table) {
                        $key = 'editAll' === $inputAdapter->get('act') ? $name.'_'.$id : $name;

                        if (null !== $inputAdapter->post($key)) {
                            $trigger = $inputAdapter->post($key);
                        }
                    }

                    if ('checkbox' === ($GLOBALS['TL_DCA'][$table]['fields'][$name]['inputType'] ?? null) && !($GLOBALS['TL_DCA'][$table]['fields'][$name]['eval']['multiple'] ?? null)) {
                        if ($trigger) {
                            $sValues[] = $name;

                            // Look for a subpalette
                            if (isset($GLOBALS['TL_DCA'][$table]['subpalettes'][$name])) {
                                $subpalettes[$name] = $GLOBALS['TL_DCA'][$table]['subpalettes'][$name];
                            }
                        }
                    } else {
                        // Use string comparison to allow "0"
                        if ('' !== (string) $trigger) {
                            $sValues[] = (string) $trigger;
                        }

                        $key = $name.'_'.$trigger;

                        // Look for a subpalette
                        if (isset($GLOBALS['TL_DCA'][$table]['subpalettes'][$key])) {
                            $subpalettes[$name] = $GLOBALS['TL_DCA'][$table]['subpalettes'][$key];
                        }
                    }
                }
            }

            // Build possible palette names from the selector values
            if ([] === $sValues) {
                $names = ['default'];
            } elseif (\count($sValues) > 1) {
                foreach ($sValues as $k => $v) {
                    // Unset selectors that just trigger sub-palettes (see #3738)
                    if (isset($GLOBALS['TL_DCA'][$table]['subpalettes'][$v])) {
                        unset($sValues[$k]);
                    }
                }

                $names = $this->combiner($sValues);
            } else {
                $names = [$sValues[0]];
            }

            // Get an existing palette
            foreach ($names as $paletteName) {
                if (isset($GLOBALS['TL_DCA'][$table]['palettes'][$paletteName])) {
                    $palette = $GLOBALS['TL_DCA'][$table]['palettes'][$paletteName];
                    break;
                }
            }

            // Include sub-palettes
            foreach ($subpalettes as $k => $v) {
                $palette = preg_replace('/\b'.preg_quote($k, '/').'\b/i', $k.',['.$k.'],'.$v.',[EOF]', $palette);
            }
        }

        // Call onpalette_callback
        if (\is_array($GLOBALS['TL_DCA'][$table]['config']['onpalette_callback'] ?? null)) {
            $systemAdapter = $this->framework->getAdapter(System::class);

            foreach ($GLOBALS['TL_DCA'][$table]['config']['onpalette_callback'] as $callback) {
                if (\is_array($callback)) {
                    $palette = $systemAdapter->importStatic($callback[0])->{$callback[1]}($palette, $dc);
                } elseif (\is_callable($callback)) {
                    $palette = $callback($palette, $dc);
                }
            }
        }

        return $palette;
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     class: string,
     *     fields: array<int, string>
     * }>
     */
    public function getBoxes(string $palette, string $table, bool $addMetaFields = false): array
    {
        $boxes = [];
        $fieldsetStates = $this->getFieldsetStates($table);

        // Add meta fields if the current user is an administrator
        if ($addMetaFields && $this->security->isGranted('ROLE_ADMIN')) {
            $adminFields = $this->getAdminFields($table);

            if ([] !== $adminFields) {
                $boxes[-1] = [
                    'key' => '',
                    'class' => '',
                    'fields' => $adminFields,
                ];
            }
        }

        foreach (StringUtil::trimsplit(';', $palette) as $k => $v) {
            $emptyCount = 1;

            $boxes[$k] = [
                'key' => '',
                'class' => '',
                'fields' => [],
            ];

            foreach (StringUtil::trimsplit(',', $v) as $vv) {
                // Legend start/stop marker
                if (preg_match('/^{.*}$/', $vv)) {
                    [$key, $class] = explode(':', substr($vv, 1, -1)) + ['', ''];

                    // Convert the ":hide" suffix from the DCA
                    if ('hide' === $class) {
                        $class = 'collapsed';
                    }

                    // Override the class if the session has a state
                    if (isset($fieldsetStates[$key])) {
                        $class = ($fieldsetStates[$key] ? '' : 'collapsed');
                    }

                    $boxes[$k]['key'] = $key;
                    $boxes[$k]['class'] = $class;

                    continue;
                }

                if (preg_match('/^\[.*]$/', $vv)) {
                    // Do not count subpalette start/stop markers when checking if box is empty
                    ++$emptyCount;
                } elseif (
                    !\is_array($GLOBALS['TL_DCA'][$table]['fields'][$vv] ?? null)
                    || (
                        DataContainer::isFieldExcluded($table, $vv)
                        && !$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $table.'::'.$vv)
                    )
                ) {
                    // Skip the field if it does not exist or is excluded for current user
                    continue;
                }

                $boxes[$k]['fields'][] = $vv;
            }

            // Unset a box if it does not contain any fields
            if (\count($boxes[$k]['fields']) < $emptyCount) {
                unset($boxes[$k]);
            }
        }

        return $boxes;
    }

    /**
     * Generates possible palette names from an array by taking the first value and
     * either adding or not adding the following values.
     */
    public function combiner(array $names): array
    {
        $return = [''];
        $names = array_values($names);

        foreach ($names as $name) {
            $buffer = [];

            foreach ($return as $k => $v) {
                $buffer[] = 0 === $k % 2 ? $v : $v.$name;
                $buffer[] = 0 === $k % 2 ? $v.$name : $v;
            }

            $return = $buffer;
        }

        return array_filter($return);
    }

    private function getFieldsetStates(string $table): array
    {
        /** @var AttributeBag $objSessionBag */
        $objSessionBag = $this->requestStack->getSession()->getBag('contao_backend');
        $fieldsetStates = $objSessionBag->get('fieldset_states');

        return $fieldsetStates[$table] ?? [];
    }

    private function getAdminFields(string $table): array
    {
        $adminFields = [];
        $columns = $this->connection->createSchemaManager()->listTableColumns($table);

        if (\array_key_exists('pid', $columns)) {
            $adminFields[] = 'pid';
        }

        if (\array_key_exists('sorting', $columns)) {
            $adminFields[] = 'sorting';
        }

        return $adminFields;
    }
}
