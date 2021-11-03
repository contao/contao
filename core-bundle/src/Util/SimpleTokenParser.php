<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Util;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0; use
 *             the Contao\CoreBundle\String\SimpleTokenParser class instead
 */
class SimpleTokenParser extends \Contao\CoreBundle\String\SimpleTokenParser
{
    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        trigger_deprecation('contao/core-bundle', '4.13', 'Using the "Contao\CoreBundle\Util\SimpleTokenParser" class has been deprecated and will no longer work in Contao 5.0. Use the "Contao\CoreBundle\String\SimpleTokenParser" class instead.');

        parent::__construct($expressionLanguage);
    }
}
