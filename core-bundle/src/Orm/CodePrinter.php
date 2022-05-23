<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Orm;

use Nette\PhpGenerator\Printer;

class CodePrinter extends Printer
{
    public int $wrapLength = 120;
    public string $indentation = '    ';
    public int $linesBetweenProperties = 0;
    public int $linesBetweenMethods = 1;
    public string $returnTypeColon = ': ';
}
