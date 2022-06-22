<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing;

use Symfony\Component\HttpFoundation\RequestStack;

class BasePathPrefixer
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function prefix(string $url): string
    {
        if (preg_match('(^(https?://|/|#|\{\{))i', $url)) {
            return $url;
        }

        return ($this->requestStack->getCurrentRequest()?->getBasePath() ?? '').'/'.$url;
    }
}
