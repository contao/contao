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
 * @deprecated Deprecated since Contao 6.1, to be removed in Contao 7.
 */
class FrontendMatcher implements RequestMatcherInterface
{
    public function matches(Request $request): bool
    {
        return 'frontend' === $request->attributes->get('_scope');
    }
}
