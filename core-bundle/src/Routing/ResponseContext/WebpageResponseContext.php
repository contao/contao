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

use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;

class WebpageResponseContext extends ResponseContext implements JsonLdProvidingResponseContextInterface
{
    /**
     * @var string
     */
    private $title = '';

    /**
     * @var string
     */
    private $metaDescription = '';
    /**
     * @var string
     */
    private $metaRobots = 'index,follow';

    /**
     * @var JsonLdManager
     */
    private $jsonLdManager;

    public function __construct(JsonLdManager $jsonLdManager)
    {
        $this->jsonLdManager = $jsonLdManager;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getMetaDescription(): string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getMetaRobots(): string
    {
        return $this->metaRobots;
    }

    public function setMetaRobots(string $metaRobots): self
    {
        $this->metaRobots = $metaRobots;

        return $this;
    }

    public function getJsonLdManager(): JsonLdManager
    {
        return $this->jsonLdManager;
    }
}
