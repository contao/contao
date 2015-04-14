<?php

namespace Contao\Fixtures;

use Contao\CoreBundle\Test\LanguageHelper;

class System
{
    protected static $arrStaticObjects = [];
    protected $arrObjects = [];

    protected function __construct()
    {
        // prevent the "Cannot call constructor" error
    }

    public static function getReferer()
    {
        return '/foo/bar';
    }

    public static function log($strText, $strFunction, $strCategory)
    {
        // ignore
    }

    public static function urlEncode($strPath)
    {
        return str_replace('%2F', '/', rawurlencode($strPath));
    }

    public static function importStatic($strClass, $strKey = null, $blnForce = false)
    {
        $strKey = $strKey ?: $strClass;

        if ($blnForce || !isset(static::$arrStaticObjects[$strKey])) {
            static::$arrStaticObjects[$strKey] = (in_array('getInstance', get_class_methods($strClass))) ? call_user_func(array($strClass, 'getInstance')) : new $strClass();
        }

        return static::$arrStaticObjects[$strKey];
    }

    public function __get($strKey)
    {
        if (!isset($this->arrObjects[$strKey])) {
            return null;
        }

        return $this->arrObjects[$strKey];
    }

    protected function import($strClass, $strKey = null, $blnForce = false)
    {
        $strKey = $strKey ?: $strClass;

        if ($blnForce || !isset($this->arrObjects[$strKey])) {
            $this->arrObjects[$strKey] = (in_array('getInstance', get_class_methods($strClass))) ? call_user_func([$strClass, 'getInstance']) : new $strClass();
        }
    }

    public static function loadLanguageFile($strName, $strLanguage=null, $blnNoCache=false)
    {
        $GLOBALS['TL_LANG'] = new LanguageHelper();
    }
}
