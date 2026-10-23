<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace App\Api;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use Contao\ApiBundle\Version\ApiVersion;

final readonly class VersionedExampleOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $property = $context[ApiVersion::CONTEXT_KEY]->isBefore('2026-03-01') ? 'legacyName' : 'displayName';

        $paths = clone $openApi->getPaths();
        $path = $paths->getPath('/contao/api/versioned-example');

        $response = new Response('A versioned example.', new \ArrayObject([
            'application/json' => new MediaType(new \ArrayObject([
                'type' => 'object',
                'properties' => [$property => ['type' => 'string']],
                'required' => [$property],
                'additionalProperties' => false,
            ])),
        ]));

        $operation = $path->getGet()->withResponses(['200' => $response]);
        $paths->addPath('/contao/api/versioned-example', $path->withGet($operation));

        return $openApi->withPaths($paths);
    }
}
