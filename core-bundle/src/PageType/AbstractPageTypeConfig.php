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

abstract class AbstractPageTypeConfig implements PageTypeConfigInterface
{
    /** @var PageTypeInterface */
    private $pageType;

    /** @var PageModel */
    private $pageModel;

    /** @var array */
    private $options;

    public function __construct(PageTypeInterface $pageType, PageModel $pageModel, array $options = [])
    {
        $this->pageType = $pageType;
        $this->pageModel = $pageModel;
        $this->options = $options;
    }

    public function getPageType(): PageTypeInterface
    {
        return $this->pageType;
    }

    public function getPageModel(): PageModel
    {
        return $this->pageModel;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOption(string $key, $value): PageTypeConfigInterface
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function setOptions(array $options): PageTypeConfigInterface
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function hasOption(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }

    public function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }
}
