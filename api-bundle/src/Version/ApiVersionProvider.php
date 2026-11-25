<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Version;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Exposes the resolved version to API collaborators without a context argument.
 */
final readonly class ApiVersionProvider
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getVersion(Request|null $request = null): ApiVersion
    {
        $request ??= $this->requestStack->getCurrentRequest();
        $version = $request?->attributes->get(ApiVersion::CONTEXT_KEY);

        return $version instanceof ApiVersion ? $version : ApiVersion::today();
    }
}
