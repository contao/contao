<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Relation;

use Contao\ApiBundle\Exception\InvalidInputException;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @template TRelation of object
 */
abstract class AbstractRelationResolver implements RelationResolverInterface
{
    /**
     * @phpstan-assert-if-true TRelation $relation
     */
    abstract public function supports(object $relation): bool;

    public function resolveToReference(mixed $value, object $relation): mixed
    {
        if (!$this->supports($relation)) {
            return $value;
        }

        return $this->mapValue(
            $value,
            function (mixed $identifier) use ($relation): RelationReference|null {
                if (!\is_int($identifier) && !\is_string($identifier)) {
                    return null;
                }

                $iri = $this->createIri($relation, $identifier);

                return null === $iri ? null : new RelationReference($identifier, $iri);
            },
        );
    }

    public function resolveToIdentifier(mixed $value, object $relation): mixed
    {
        if (!$this->supports($relation)) {
            return $value;
        }

        return $this->mapValue($value, fn (mixed $reference): mixed => $this->getIdentifier($relation, $this->getReferenceIri($reference)));
    }

    private function mapValue(mixed $value, callable $callback): mixed
    {
        if (\is_array($value) && !$this->isReference($value)) {
            return array_map(fn (mixed $item): mixed => $this->mapValue($item, $callback), $value);
        }

        if ($this->isEmptyValue($value)) {
            return null;
        }

        return $callback($value);
    }

    protected function isEmptyValue(mixed $value): bool
    {
        return null === $value;
    }

    private function isReference(array $value): bool
    {
        return \array_key_exists('iri', $value) || \array_key_exists('@id', $value);
    }

    private function getReferenceIri(mixed $value): mixed
    {
        if ($value instanceof RelationReference) {
            return $value->iri;
        }

        if (\is_array($value)) {
            return $value['iri'] ?? $value['@id'] ?? null;
        }

        // Also accept JSON-LD's compact IRI representation
        return $value;
    }

    protected function matchIri(mixed $iri, RouterInterface $router): array
    {
        if (!\is_string($iri) || !\is_string($path = parse_url($iri, PHP_URL_PATH)) || '' === $path) {
            throw new InvalidInputException('Relation values must be valid IRIs.');
        }

        // An IRI identifies the resource to read, regardless of the current request method
        $context = $router->getContext();
        $method = $context->getMethod();

        $context->setMethod('GET');

        try {
            $parameters = $router->match($path);
        } catch (RoutingExceptionInterface) {
            throw new InvalidInputException('The relation IRI does not match an API resource.');
        } finally {
            $context->setMethod($method);
        }

        return $parameters;
    }

    /**
     * @param TRelation $relation
     */
    abstract protected function createIri(object $relation, mixed $identifier): string|null;

    /**
     * @param TRelation $relation
     */
    abstract protected function getIdentifier(object $relation, mixed $iri): mixed;
}
