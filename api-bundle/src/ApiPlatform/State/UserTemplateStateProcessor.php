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
use Contao\ApiBundle\UserTemplate\TemplateStudioClient;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<UserTemplateOperation, Response>
 */
final class UserTemplateStateProcessor implements ProcessorInterface
{
    public function __construct(private readonly TemplateStudioClient $client)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        return $this->client->call(
            $operation->getExtraProperties()['template_studio_operation'],
            $uriVariables['identifier'],
            $data->theme,
            $data->parameters,
        );
    }
}
