<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PreviewUrlConvertEvent extends Event
{
    /**
     * @var string
     */
    private $url;

    /**
     * Returns the URL.
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Sets the URL.
     *
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
