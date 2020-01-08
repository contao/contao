<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\PageType;

use Contao\PageModel;
use Symfony\Component\Routing\Route;

interface PageTypeInterface
{
    public function getName(): string;

    public function getAvailableParameters(): array;

    public function createRoute(PageModel $pageModel, bool $prependLocale, string $urlSuffix): Route;

    public function createPageTypeConfig(PageModel $pageModel): PageTypeConfigInterface;
}
