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

use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\System;

/**
 * @internal
 */
class ButtonsBuilder
{
    public function generateSubmitButtons(string $strTable, bool $hasPtable, bool $hasCreatePermission, DataContainer $dc): string
    {
        $arrButtons = [];
        $arrButtons['save'] = '<button type="submit" name="save" id="save" class="tl_submit" accesskey="s" data-turbo-frame="_self">'.$GLOBALS['TL_LANG']['MSC']['save'].'</button>';

        if (!Input::get('nb')) {
            $arrButtons['saveNclose'] = '<button type="submit" name="saveNclose" id="saveNclose" class="tl_submit" accesskey="c" data-action="contao--scroll-offset#discard">'.$GLOBALS['TL_LANG']['MSC']['saveNclose'].'</button>';

            if (!Input::get('nc')) {
                if (!($GLOBALS['TL_DCA'][$strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$strTable]['config']['notCreatable'] ?? null) && $hasCreatePermission) {
                    $arrButtons['saveNcreate'] = '<button type="submit" name="saveNcreate" id="saveNcreate" class="tl_submit" accesskey="n" data-action="contao--scroll-offset#discard">'.$GLOBALS['TL_LANG']['MSC']['saveNcreate'].'</button>';

                    if (!($GLOBALS['TL_DCA'][$strTable]['config']['notCopyable'] ?? null)) {
                        $arrButtons['saveNduplicate'] = '<button type="submit" name="saveNduplicate" id="saveNduplicate" class="tl_submit" accesskey="d" data-action="contao--scroll-offset#discard">'.$GLOBALS['TL_LANG']['MSC']['saveNduplicate'].'</button>';
                    }
                }

                if ($GLOBALS['TL_DCA'][$strTable]['config']['switchToEdit'] ?? null) {
                    $arrButtons['saveNedit'] = '<button type="submit" name="saveNedit" id="saveNedit" class="tl_submit" accesskey="e" data-action="contao--scroll-offset#discard">'.$GLOBALS['TL_LANG']['MSC']['saveNedit'].'</button>';
                }

                if ($hasPtable || ($GLOBALS['TL_DCA'][$strTable]['config']['switchToEdit'] ?? null) || DataContainer::MODE_PARENT === ($GLOBALS['TL_DCA'][$strTable]['list']['sorting']['mode'] ?? null)) {
                    $arrButtons['saveNback'] = '<button type="submit" name="saveNback" id="saveNback" class="tl_submit" accesskey="g" data-action="contao--scroll-offset#discard">'.$GLOBALS['TL_LANG']['MSC']['saveNback'].'</button>';
                }
            }
        }

        // Call the buttons_callback (see #4691)
        if (\is_array($GLOBALS['TL_DCA'][$strTable]['edit']['buttons_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA'][$strTable]['edit']['buttons_callback'] as $callback) {
                if (\is_array($callback)) {
                    $arrButtons = System::importStatic($callback[0])->{$callback[1]}($arrButtons, $dc);
                } elseif (\is_callable($callback)) {
                    $arrButtons = $callback($arrButtons, $dc);
                }
            }
        }

        if (\count($arrButtons) < 3) {
            $strButtons = implode(' ', $arrButtons);
        } else {
            $strButtons = array_shift($arrButtons).' ';
            $strButtons .= '<div class="split-button">';
            $strButtons .= array_shift($arrButtons).'<button type="button" id="sbtog">'.Image::getHtml('navcol.svg').'</button> <ul class="invisible">';

            foreach ($arrButtons as $strButton) {
                $strButtons .= '<li>'.$strButton.'</li>';
            }

            $strButtons .= '</ul></div>';
        }

        return $strButtons;
    }

    public function generateSubmitAllButtons(string $strTable, DataContainer $dc): string
    {
        $arrButtons = [];
        $arrButtons['save'] = '<button type="submit" name="save" id="save" class="tl_submit" accesskey="s">'.$GLOBALS['TL_LANG']['MSC']['save'].'</button>';
        $arrButtons['saveNclose'] = '<button type="submit" name="saveNclose" id="saveNclose" class="tl_submit" accesskey="c" data-action="contao--scroll-offset#discard">'.$GLOBALS['TL_LANG']['MSC']['saveNclose'].'</button>';

        // Call the buttons_callback (see #4691)
        if (\is_array($GLOBALS['TL_DCA'][$strTable]['edit']['buttons_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA'][$strTable]['edit']['buttons_callback'] as $callback) {
                if (\is_array($callback)) {
                    $arrButtons = System::importStatic($callback[0])->{$callback[1]}($arrButtons, $dc);
                } elseif (\is_callable($callback)) {
                    $arrButtons = $callback($arrButtons, $dc);
                }
            }
        }

        if (\count($arrButtons) < 3) {
            $strButtons = implode(' ', $arrButtons);
        } else {
            $strButtons = array_shift($arrButtons).' ';
            $strButtons .= '<div class="split-button">';
            $strButtons .= array_shift($arrButtons).'<button type="button" id="sbtog">'.Image::getHtml('navcol.svg').'</button> <ul class="invisible">';

            foreach ($arrButtons as $strButton) {
                $strButtons .= '<li>'.$strButton.'</li>';
            }

            $strButtons .= '</ul></div>';
        }

        return $strButtons;
    }

    public function generateSelectButtons(string $strTable, bool $isSortable, DataContainer $dc): string
    {
        $arrButtons = [];

        if (!($GLOBALS['TL_DCA'][$strTable]['config']['notEditable'] ?? null)) {
            $arrButtons['edit'] = '<button type="submit" name="edit" id="edit" class="tl_submit" accesskey="s">'.$GLOBALS['TL_LANG']['MSC']['editSelected'].'</button>';
        }

        if (!($GLOBALS['TL_DCA'][$strTable]['config']['notDeletable'] ?? null)) {
            $arrButtons['delete'] = '<button type="submit" name="delete" id="delete" class="tl_submit" accesskey="d" onclick="return confirm(\''.$GLOBALS['TL_LANG']['MSC']['delAllConfirm'].'\')">'.$GLOBALS['TL_LANG']['MSC']['deleteSelected'].'</button>';
        }

        if (!($GLOBALS['TL_DCA'][$strTable]['config']['notCopyable'] ?? null)) {
            $arrButtons['copy'] = '<button type="submit" name="copy" id="copy" class="tl_submit" accesskey="c">'.$GLOBALS['TL_LANG']['MSC']['copySelected'].'</button>';

            if ($isSortable) {
                $arrButtons['copyMultiple'] = '<button type="submit" name="copyMultiple" id="copyMultiple" class="tl_submit" accesskey="m">'.$GLOBALS['TL_LANG']['MSC']['copyMultiple'].'</button>';
            }
        }

        if ($isSortable && !($GLOBALS['TL_DCA'][$strTable]['config']['notSortable'] ?? null)) {
            $arrButtons['cut'] = '<button type="submit" name="cut" id="cut" class="tl_submit" accesskey="x">'.$GLOBALS['TL_LANG']['MSC']['moveSelected'].'</button>';
        }

        if (!($GLOBALS['TL_DCA'][$strTable]['config']['notEditable'] ?? null)) {
            $arrButtons['override'] = '<button type="submit" name="override" id="override" class="tl_submit" accesskey="v">'.$GLOBALS['TL_LANG']['MSC']['overrideSelected'].'</button>';
        }

        // Call the buttons_callback (see #4691)
        if (\is_array($GLOBALS['TL_DCA'][$strTable]['select']['buttons_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA'][$strTable]['select']['buttons_callback'] as $callback) {
                if (\is_array($callback)) {
                    $arrButtons = System::importStatic($callback[0])->{$callback[1]}($arrButtons, $dc);
                } elseif (\is_callable($callback)) {
                    $arrButtons = $callback($arrButtons, $dc);
                }
            }
        }

        if (\count($arrButtons) < 3) {
            $strButtons = implode(' ', $arrButtons);
        } else {
            $strButtons = array_shift($arrButtons).' ';
            $strButtons .= '<div class="split-button">';
            $strButtons .= array_shift($arrButtons).'<button type="button" id="sbtog">'.Image::getHtml('navcol.svg').'</button> <ul class="invisible">';

            foreach ($arrButtons as $strButton) {
                $strButtons .= '<li>'.$strButton.'</li>';
            }

            $strButtons .= '</ul></div>';
        }

        return $strButtons;
    }

    public function generateUploadButtons(string $strTable, DataContainer $dc): string
    {
        $arrButtons = [];
        $arrButtons['upload'] = '<button type="submit" name="upload" class="tl_submit" accesskey="s">'.$GLOBALS['TL_LANG'][$strTable]['move'][0].'</button>';
        $arrButtons['uploadNback'] = '<button type="submit" name="uploadNback" class="tl_submit" accesskey="c">'.$GLOBALS['TL_LANG'][$strTable]['uploadNback'].'</button>';

        // Call the buttons_callback (see #4691)
        if (\is_array($GLOBALS['TL_DCA'][$strTable]['edit']['buttons_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA'][$strTable]['edit']['buttons_callback'] as $callback) {
                if (\is_array($callback)) {
                    $arrButtons = System::importStatic($callback[0])->{$callback[1]}($arrButtons, $dc);
                } elseif (\is_callable($callback)) {
                    $arrButtons = $callback($arrButtons, $dc);
                }
            }
        }

        if (\count($arrButtons) < 3) {
            $strButtons = implode(' ', $arrButtons);
        } else {
            $strButtons = array_shift($arrButtons).' ';
            $strButtons .= '<div class="split-button">';
            $strButtons .= array_shift($arrButtons).'<button type="button" id="sbtog">'.Image::getHtml('navcol.svg').'</button> <ul class="invisible">';

            foreach ($arrButtons as $strButton) {
                $strButtons .= '<li>'.$strButton.'</li>';
            }

            $strButtons .= '</ul></div>';
        }

        return $strButtons;
    }
}
