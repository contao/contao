<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Page\Metadata;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PageMetadataContainer
{
    public const REQUEST_ATTRIBUTE = '_contao_page_metadata';
    private const JSON_LD_MANAGER = 'json-ld-manager';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(RequestStack $requestStack, EventDispatcherInterface $eventDispatcher)
    {
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getJsonLdManager(): JsonLdManager
    {
        return $this->getServiceFromCurrentMasterRequest(
            self::JSON_LD_MANAGER,
            static function (PageMetadataContainer $pageMetadataContainer) {
                return new JsonLdManager($pageMetadataContainer);
            }
        );
    }

    public function getCurrentMasterRequest(): Request
    {
        $request = $this->requestStack->getMasterRequest();

        if (null === $request) {
            throw new \BadMethodCallException('Cannot access PageMetadataContainer without request context.');
        }

        return $request;
    }

    /**
     * @return mixed
     */
    private function getServiceFromCurrentMasterRequest(string $serviceKey, \Closure $initializer)
    {
        $request = $this->getCurrentMasterRequest();

        $attribute = $request->attributes->get(self::REQUEST_ATTRIBUTE, []);

        if (!\array_key_exists($serviceKey, $attribute)) {
            $attribute[$serviceKey] = $initializer($this);
            $request->attributes->set(self::REQUEST_ATTRIBUTE, $attribute);
        }

        return $attribute[$serviceKey];
    }
}
