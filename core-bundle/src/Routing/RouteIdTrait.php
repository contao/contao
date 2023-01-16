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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Model\Collection;
use Contao\PageModel;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

trait RouteIdTrait
{
    /**
     * @return array<int>
     */
    protected function getIdsFromRouteNames(array $names, string $table): array
    {
        $ids = [];

        foreach ($names as $name) {
            if (!str_starts_with($name, $table.'.')) {
                continue;
            }

            [, $id] = explode('.', (string) $name);

            if (!preg_match('/^[1-9]\d*$/', $id)) {
                continue;
            }

            $ids[] = (int) $id;
        }

        return array_values(array_unique($ids));
    }
}
