<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Matcher;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * @internal
 */
class RequestMatcher implements RequestMatcherInterface
{
    public function __construct(
        private readonly string $scope,
        private readonly bool $stateless,
    ) {
    }

    public function matches(Request $request): bool
    {
        return $this->scope === $request->attributes->get('_scope')
            && $this->stateless === $request->attributes->getBoolean('_stateless');
    }
}
