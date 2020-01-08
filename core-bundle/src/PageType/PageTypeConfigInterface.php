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

interface PageTypeConfigInterface
{
    public function getPageType(): PageTypeInterface;

    public function getPageModel(): PageModel;

    public function getOptions(): array;

    public function setOption(string $key, $value): self;

    public function setOptions(array $options): self;

    public function hasOption(string $key): bool;

    public function getOption(string $key, $default = null);
}
