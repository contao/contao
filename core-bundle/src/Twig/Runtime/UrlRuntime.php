<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\Validator;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

final class UrlRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function prefixUrl(string $url): string
    {
        if (!Validator::isRelativeUrl($url)) {
            return $url;
        }

        return $this->requestStack->getCurrentRequest()?->getBasePath().'/'.$url;
    }
}
