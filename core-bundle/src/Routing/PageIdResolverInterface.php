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

interface PageIdResolverInterface
{
    /**
     * @param array<string>|null $names
     * @return array<int>
     */
    public function resolvePageIds(?array $names): array;
}
