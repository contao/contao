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

use Symfony\Component\Routing\Route;

class RedirectRoute extends Route
{
    public const TARGET_URL = 'target_url';

    public function __construct(string $targetUrl)
    {
        $options[self::TARGET_URL] = $targetUrl;

        parent::__construct('', [], [], $options);
    }
}
