<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\PageIdResolver;

use Contao\CoreBundle\Routing\PageIdResolverInterface;

class SimplePageIdResolver implements PageIdResolverInterface
{
    public function resolvePageIds(?array $names): array
    {
        $pageIds = [];

        if (null === $names) {
            return $pageIds;
        }

        foreach ($names as $name) {
            if (0 !== strncmp($name, 'tl_page.', 8)) {
                continue;
            }

            [, $id] = explode('.', $name);

            if (!is_numeric($id)) {
                continue;
            }

            $pageIds[] = (int) $id;
        }

        return $pageIds;
    }
}
