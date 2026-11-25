<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\Serializer;

use ApiPlatform\State\SerializerContextBuilderInterface;
use Contao\ApiBundle\Version\ApiVersion;
use Contao\ApiBundle\Version\ApiVersionProvider;
use Symfony\Component\HttpFoundation\Request;

final readonly class ApiVersionContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        private SerializerContextBuilderInterface $decorated,
        private ApiVersionProvider $versionProvider,
    ) {
    }

    public function createFromRequest(Request $request, bool $normalization, array|null $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $context[ApiVersion::CONTEXT_KEY] = $this->versionProvider->getVersion($request);

        return $context;
    }
}
