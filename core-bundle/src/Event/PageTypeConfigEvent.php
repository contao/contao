<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Contao\CoreBundle\PageType\PageTypeConfigInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PageTypeConfigEvent extends Event
{
    /**
     * @var PageTypeConfigInterface
     */
    private $pageTypeConfig;

    public function __construct(PageTypeConfigInterface $pageTypeConfig)
    {
        $this->pageTypeConfig = $pageTypeConfig;
    }

    public function getPageTypeConfig(): PageTypeConfigInterface
    {
        return $this->pageTypeConfig;
    }
}
