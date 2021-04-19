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

use Symfony\Component\HttpFoundation\RequestStack;

class PageMetadataContainer
{
    public const REQUEST_ATTRIBUTE = '_contao_page_metadata';
    private const JSON_LD_MANAGER = 'json-ld-manager';

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getJsonLdManager(): JsonLdManager
    {
        return $this->getServiceFromCurrentMasterRequest(
            self::JSON_LD_MANAGER,
            static function () {
                return new JsonLdManager();
            }
        );
    }

    /**
     * @return mixed
     */
    private function getServiceFromCurrentMasterRequest(string $serviceKey, \Closure $initializer)
    {
        $request = $this->requestStack->getMasterRequest();

        if (null === $request) {
            throw new \BadMethodCallException('Cannot access PageMetadataContainer without request context.');
        }

        $attribute = $request->attributes->get(self::REQUEST_ATTRIBUTE, []);

        if (!\array_key_exists($serviceKey, $attribute)) {
            $attribute[$serviceKey] = $initializer();
            $request->attributes->set(self::REQUEST_ATTRIBUTE, $attribute);
        }

        return $attribute[$serviceKey];
    }
}
