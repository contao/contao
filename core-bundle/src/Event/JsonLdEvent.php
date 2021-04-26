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

use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Symfony\Contracts\EventDispatcher\Event;

class JsonLdEvent extends Event
{
    /**
     * @var JsonLdManager
     */
    private $jsonLdManager;

    public function __construct(JsonLdManager $jsonLdManager)
    {
        $this->jsonLdManager = $jsonLdManager;
    }

    public function getJsonLdManager(): JsonLdManager
    {
        return $this->jsonLdManager;
    }
}
