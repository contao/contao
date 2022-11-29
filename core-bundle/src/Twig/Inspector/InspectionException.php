<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Inspector;

class InspectionException extends \RuntimeException
{
    public function __construct(string $templateName, \Throwable|null $previous = null)
    {
        parent::__construct(sprintf('Could not inspect template "%s".', $templateName), 0, $previous);
    }
}
