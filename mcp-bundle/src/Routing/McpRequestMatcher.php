<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class McpRequestMatcher implements RequestMatcherInterface
{
    final public const string ROUTE = 'contao_mcp_backend';

    public function matches(Request $request): bool
    {
        return self::ROUTE === $request->attributes->get('_route');
    }
}
