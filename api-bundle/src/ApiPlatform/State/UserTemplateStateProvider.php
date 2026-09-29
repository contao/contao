<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Contao\ApiBundle\UserTemplate\TemplateStudioClient;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @implements ProviderInterface<JsonResponse>
 */
final class UserTemplateStateProvider implements ProviderInterface
{
    public function __construct(private readonly TemplateStudioClient $client)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $request = $context['request'];
        $themeSlug = $request->query->get('theme');

        return isset($uriVariables['name'])
            ? $this->client->read($uriVariables['name'], $themeSlug)
            : $this->client->discover($themeSlug);
    }
}
