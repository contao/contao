<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Validator;

/**
 * @Callback(table="tl_page", target="fields.language.save")
 */
class PageLanguageListener
{
    /**
     * @var bool
     */
    private $legacyRouting;

    public function __construct(bool $legacyRouting)
    {
        $this->legacyRouting = $legacyRouting;
    }

    public function __invoke($value)
    {
        if ($this->legacyRouting) {
            if (!Validator::isLanguage($value)) {
                throw new \RuntimeException($GLOBALS['TL_LANG']['ERR']['language']);
            }

            return $value;
        }

        return LocaleUtil::canonicalize($value);
    }
}
