<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

class WebpageResponseContext extends ResponseContext
{
    /**
     * @var string
     */
    private $title = '';

    /**
     * @var string
     */
    private $description = '';
    /**
     * @var string
     */
    private $robotsMetaTagContent = 'index,follow';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getRobotsMetaTagContent(): string
    {
        return $this->robotsMetaTagContent;
    }

    public function setRobotsMetaTagContent(string $robotsMetaTagContent): self
    {
        $this->robotsMetaTagContent = $robotsMetaTagContent;

        return $this;
    }
}
