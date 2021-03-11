<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Backend;

/**
 * Service that helps to share state in the backend between controllers. Allows you to set the current page title
 * from inside subrequest controllers. Subrequest controllers must be using inline/forward rendering,
 * otherwise the subrequest is detached from the controller that generates the main response.
 *
 * @experimental Subject to change, will become part of the API in Contao 4.13.
 */
class BackendState
{
    private $headline;
    private $title;

    public function __construct()
    {
        $this->headline = '';
        $this->title = '';
    }

    public function getHeadline(): string
    {
        return $this->headline;
    }

    public function setHeadline(string $headline): void
    {
        $this->headline = $headline;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
