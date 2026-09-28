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
use ApiPlatform\State\ProcessorInterface;
use Contao\ApiBundle\Dto\UserTemplateOperation;
use Contao\ApiBundle\Dto\UserTemplateUpdate;
use Contao\ApiBundle\UserTemplate\TemplateStudioClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<UserTemplateOperation|UserTemplateUpdate|null, Response>
 */
final class UserTemplateStateProcessor implements ProcessorInterface
{
    public function __construct(private readonly TemplateStudioClient $client)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        $operationName = $operation->getExtraProperties()['template_studio_operation'];
        $request = $context['request'] ?? null;
        $theme = $request instanceof Request ? $request->query->get('theme') : null;

        if ('delete' === $operationName) {
            return $this->client->call($operationName, $uriVariables['name'], $theme, ['confirm_delete' => true]);
        }

        if ('save' === $operationName) {
            if (!$data instanceof UserTemplateUpdate) {
                throw new \LogicException('The user template update input is missing.');
            }

            return $this->client->call($operationName, $uriVariables['name'], $theme, ['code' => $data->code]);
        }

        if (!$data instanceof UserTemplateOperation) {
            throw new \LogicException('The user template operation input is missing.');
        }

        return $this->client->call($operationName, $data->name, $theme, $data->parameters);
    }
}
