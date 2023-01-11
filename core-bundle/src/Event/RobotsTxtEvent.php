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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\RobotsTxt\File\File;

class RobotsTxtEvent extends Event
{
    public function __construct(private File $file, private Request $request, private PageModel $rootPage)
    {
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
