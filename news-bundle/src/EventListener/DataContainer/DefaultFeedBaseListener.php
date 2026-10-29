<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback('tl_page', 'fields.feedBase.default')]
class DefaultFeedBaseListener
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function __invoke(): string|null
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return null;
        }

        return rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/').'/';
    }
}
