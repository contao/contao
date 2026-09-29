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

        if ('themes' === ($operation->getExtraProperties()['template_studio_action'] ?? null)) {
            return $this->client->themes();
        }

        if (!isset($uriVariables['name'])) {
            $response = $this->client->discover($themeSlug);
            $query = trim($request->query->getString('query'));

            if ('' === $query) {
                return $response;
            }

            $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $data['tree'] = $this->filterTree($data['tree'] ?? [], $query);

            return new JsonResponse($data);
        }

        return $this->client->read($uriVariables['name'], $themeSlug);
    }

    private function filterTree(array $tree, string $query): array
    {
        $filtered = [];

        foreach ($tree as $key => $node) {
            if (isset($node['identifier'])) {
                if (str_contains(strtolower($node['identifier']), strtolower($query))) {
                    $filtered[$key] = $node;
                }

                continue;
            }

            if ([] !== ($children = $this->filterTree($node, $query))) {
                $filtered[$key] = $children;
            }
        }

        return $filtered;
    }
}
