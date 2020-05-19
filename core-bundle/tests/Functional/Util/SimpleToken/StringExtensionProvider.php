<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional\Util\SimpleToken;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class StringExtensionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            ExpressionFunction::fromPhp('strtoupper'),
        ];
    }
}
