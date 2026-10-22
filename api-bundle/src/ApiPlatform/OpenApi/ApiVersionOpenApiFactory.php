<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Reference;
use ApiPlatform\OpenApi\OpenApi;
use Contao\ApiBundle\Version\ApiVersion;
use Contao\ApiBundle\Version\ApiVersionProvider;

final readonly class ApiVersionOpenApiFactory implements OpenApiFactoryInterface
{
    private const PARAMETER = 'ContaoApiVersion';

    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private ApiVersionProvider $versionProvider,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $context[ApiVersion::CONTEXT_KEY] ??= $this->versionProvider->getVersion($context['request'] ?? null);
        $version = $context[ApiVersion::CONTEXT_KEY];
        $openApi = ($this->decorated)($context);
        $today = (string) ApiVersion::today();

        $parameters = clone ($openApi->getComponents()->getParameters() ?? new \ArrayObject());
        $parameters[self::PARAMETER] = new Parameter(
            name: ApiVersion::HEADER,
            in: 'header',
            description: $this->getVersionDescription($today),
            schema: ['type' => 'string', 'format' => 'date'],
            example: (string) $version,
        );

        $paths = clone $openApi->getPaths();
        $reference = new Reference('#/components/parameters/'.self::PARAMETER);

        foreach ($paths->getPaths() as $path => $item) {
            $pathParameters = $item->getParameters() ?? [];

            if (!array_any($pathParameters, static fn (mixed $parameter): bool => $parameter instanceof Reference && $parameter->getRef() === $reference->getRef())) {
                $paths->addPath($path, $item->withParameters([...$pathParameters, $reference]));
            }
        }

        return $openApi
            ->withInfo($openApi->getInfo()->withVersion((string) $version))
            ->withComponents($openApi->getComponents()->withParameters($parameters))
            ->withPaths($paths)
        ;
    }

    private function getVersionDescription(string $today): string
    {
        return \sprintf(<<<'MARKDOWN'
            Selects the API contract applicable on a calendar date for built-in and custom API resources.
            When starting an integration, send today's date, for example `%1$s: %2$s`, and pin that date
            in your client to preserve compatibility.

            Omitting this header or computing today's UTC date for each request automatically opts into
            new breaking changes as the API is upgraded. Different requests may select different dates,
            allowing endpoints to be migrated independently. Dates do not need to match a Contao release.

            A breaking change introduced on `2026-11-01` preserves its previous behavior for dates before
            `2026-11-01` and uses its new behavior for dates on or after that date. The selected contract
            applies to request formats, validation, API behavior, response formats and errors.

            When supplied, the header must contain exactly one valid calendar date in `YYYY-MM-DD`
            format. Empty or repeated values, invalid dates and dates after the current
            UTC date return HTTP 400 before the API controller executes.

            Responses echo the resolved date in `%1$s` and include `Vary: %1$s` alongside existing Vary
            fields. This also applies to direct responses and error responses. Invalid version headers
            have no resolved date to echo. Caches must distinguish responses by this request header.

            For browser clients on another origin, allow `%1$s` as a request header in the server's CORS
            configuration and expose the response header if JavaScript needs to read it.

            Select a date in the documentation's API version field to display the specification for that
            date. Documentation downloads use the selected version as well.
            MARKDOWN,
            ApiVersion::HEADER,
            $today,
        );
    }
}
