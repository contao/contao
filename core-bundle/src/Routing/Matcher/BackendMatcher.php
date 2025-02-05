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

class BackendMatcher implements RequestMatcherInterface
{
    public function matches(Request $request): bool
    {
        return 'backend' === $request->attributes->get('_scope');
    }
}
