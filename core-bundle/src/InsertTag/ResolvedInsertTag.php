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

final class ResolvedInsertTag extends InsertTag
{
    /**
     * @param list<InsertTagFlag> $flags
     */
    public function __construct(string $name, ResolvedParameters $parameters, array $flags)
    {
        parent::__construct($name, $parameters, $flags);
    }

    public function getParameters(): ResolvedParameters
    {
        $parameters = parent::getParameters();

        if (!$parameters instanceof ResolvedParameters) {
            throw new \TypeError(sprintf('%s(): Return value must be of type %s, got %s', __METHOD__, ResolvedParameters::class, $parameters::class));
        }

        return $parameters;
    }
}
