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

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameterInterface;
use ApiPlatform\State\Exception\ParameterNotSupportedException;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<object>
 */
final readonly class QueryParameterStateProvider implements ProviderInterface
{
    /**
     * @param ProviderInterface<object> $decorated
     */
    public function __construct(
        private ProviderInterface $decorated,
        private PaginationOptions $paginationOptions,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        try {
            return $this->decorated->provide($operation, $uriVariables, $context);
        } catch (ParameterNotSupportedException $exception) {
            $names = $this->getQueryParameterNames($operation);

            throw new BadRequestHttpException(\sprintf('The query parameter "%s" is not supported. %s', $exception->getInstance(), $names ? 'Supported query parameters: '.implode(', ', $names).'.' : 'This operation does not accept query parameters.'), $exception);
        }
    }

    /**
     * @return list<string>
     */
    private function getQueryParameterNames(Operation $operation): array
    {
        $names = [];

        foreach ($operation->getParameters() ?? [] as $parameter) {
            if ($parameter instanceof QueryParameterInterface) {
                $names[] = $parameter->getKey();
            }
        }

        if (!$operation instanceof CollectionOperationInterface || !$operation instanceof HttpOperation) {
            return $names;
        }

        if ($operation->getPaginationEnabled() ?? $this->paginationOptions->isPaginationEnabled()) {
            $names[] = $this->paginationOptions->getPaginationPageParameterName();

            if ($operation->getPaginationClientItemsPerPage() ?? $this->paginationOptions->getClientItemsPerPage()) {
                $names[] = $this->paginationOptions->getItemsPerPageParameterName();
            }
        }

        if ($operation->getPaginationClientEnabled() ?? $this->paginationOptions->isPaginationClientEnabled()) {
            $names[] = $this->paginationOptions->getPaginationClientEnabledParameterName();
        }

        if ($operation->getPaginationClientPartial() ?? $this->paginationOptions->isClientPartialPaginationEnabled()) {
            $names[] = $this->paginationOptions->getPartialPaginationParameterName();
        }

        return $names;
    }
}
