<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\DataContainer;

use ApiPlatform\Metadata\Operation;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class DataContainerContext
{
    /**
     * @param list<array{table: string, id: int}> $parents
     */
    private function __construct(private readonly array $parents)
    {
    }

    public static function fromOperation(Operation $operation, array $uriVariables): self
    {
        $parents = [];

        foreach ($operation->getExtraProperties()['contao']['parents'] ?? [] as $parent) {
            $parameter = $parent['parameter'] ?? null;
            $table = $parent['table'] ?? null;
            $id = \is_string($parameter) ? filter_var($uriVariables[$parameter] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;

            if (!\is_string($table) || '' === $table || false === $id) {
                throw new UnprocessableEntityHttpException('The parent path contains an invalid record identifier.');
            }

            $parents[] = ['table' => $table, 'id' => $id];
        }

        $recursive = $operation->getExtraProperties()['contao']['recursive_parent'] ?? null;

        if (\is_array($recursive)) {
            self::appendRecursiveParents($parents, $recursive, $uriVariables);
        }

        return new self($parents);
    }

    /**
     * @param list<array{table: string, id: int}> $parents
     */
    private static function appendRecursiveParents(array &$parents, array $recursive, array $uriVariables): void
    {
        $parameter = $recursive['parameter'] ?? null;
        $segment = $recursive['segment'] ?? null;
        $table = $recursive['table'] ?? null;
        $value = \is_string($parameter) ? $uriVariables[$parameter] ?? null : null;

        if (!\is_string($table) || !\is_string($segment) || !\is_string($value)) {
            throw new UnprocessableEntityHttpException('The nested parent path is invalid.');
        }

        foreach (explode('/'.$segment.'/', $value) as $id) {
            $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if (false === $id) {
                throw new UnprocessableEntityHttpException('The nested parent path contains an invalid record identifier.');
            }

            $parents[] = ['table' => $table, 'id' => $id];
        }
    }

    /**
     * @return list<array{table: string, id: int}>
     */
    public function getParents(): array
    {
        return $this->parents;
    }

    /**
     * @return array{table: string, id: int}|null
     */
    public function getImmediateParent(): array|null
    {
        return $this->parents[array_key_last($this->parents)] ?? null;
    }
}
