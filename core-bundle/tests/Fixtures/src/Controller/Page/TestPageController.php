<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Controller\Page;

use Contao\CoreBundle\Routing\Page\ContentCompositionInterface;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TestPageController extends AbstractController implements DynamicRouteInterface, ContentCompositionInterface
{
    public function __invoke(): void
    {
    }

    public function supportsContentComposition(PageModel $pageModel): bool
    {
        return false;
    }

    public function configurePageRoute(PageRoute $route): void
    {
    }

    public function getUrlSuffixes(): array
    {
        return [];
    }
}
