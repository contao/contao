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
use Contao\DataContainer;
use Contao\Input;
use Contao\System;

/**
 * @internal
 */
class PaletteBuilder
{
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
                foreach ($GLOBALS['TL_DCA'][$table]['palettes']['__selector__'] as $name) {
                    $trigger = $currentRow[$name] ?? null;

                    // Overwrite the trigger
                    if (Input::post('FORM_SUBMIT') === $table) {
                        $key = 'editAll' === Input::get('act') ? $name.'_'.$id : $name;

                        if (null !== Input::post($key)) {
                            $trigger = Input::post($key);
                        }
                    }

                    if (($GLOBALS['TL_DCA'][$table]['fields'][$name]['inputType'] ?? null) === 'checkbox' && !($GLOBALS['TL_DCA'][$table]['fields'][$name]['eval']['multiple'] ?? null)) {
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
            if (empty($sValues)) {
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
            foreach ($GLOBALS['TL_DCA'][$table]['config']['onpalette_callback'] as $callback) {
                if (\is_array($callback)) {
                    $palette = System::importStatic($callback[0])->{$callback[1]}($palette, $dc);
                } elseif (\is_callable($callback)) {
                    $palette = $callback($palette, $dc);
                }
            }
        }

        return $palette;
    }

    /**
     * Generate possible palette names from an array by taking the first value and
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
}
