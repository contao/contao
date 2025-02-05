<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag;

final class ParsedInsertTag extends InsertTag
{
    /**
     * @param list<InsertTagFlag> $flags
     */
    public function __construct(string $name, ParsedParameters $parameters, array $flags)
    {
        parent::__construct($name, $parameters, $flags);
    }

    public function getParameters(): ParsedParameters
    {
        $parameters = parent::getParameters();

        if (!$parameters instanceof ParsedParameters) {
            throw new \TypeError(\sprintf('%s(): Return value must be of type %s, got %s', __METHOD__, ParsedParameters::class, $parameters::class));
        }

        return $parameters;
    }
}
