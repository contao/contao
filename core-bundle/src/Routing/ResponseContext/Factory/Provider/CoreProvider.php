<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext\Factory\Provider;

use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextInterface;
use Contao\CoreBundle\Routing\ResponseContext\WebpageContext;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CoreProvider implements ResponseContextProviderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function supports(string $responseContextClassName): bool
    {
        return \in_array($responseContextClassName, [
            ResponseContext::class,
            WebpageContext::class,
        ], true);
    }

    public function create(string $responseContextClassName): ResponseContextInterface
    {
        if (WebpageContext::class === $responseContextClassName) {
            return new WebpageContext(new JsonLdManager($this->eventDispatcher));
        }

        return new ResponseContext();
    }
}
