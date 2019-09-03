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

use Contao\PageModel;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use webignition\RobotsTxt\File\File;

class RobotsTxtEvent extends Event
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var PageModel
     */
    private $rootPage;

    public function __construct(File $file, Request $request, PageModel $rootPage)
    {
        $this->file = $file;
        $this->request = $request;
        $this->rootPage = $rootPage;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getRootPage(): PageModel
    {
        return $this->rootPage;
    }
}
